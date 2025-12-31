<?php
/**
 * LounGenie Portal - Microsoft Graph Client (App-only)
 * Lightweight helper for inbound/outbound mail via Graph
 */

if (! defined('ABSPATH') ) {
    exit;
}

class LGP_Graph_Client
{
    private $tenant_id;
    private $client_id;
    private $client_secret;
    private $mailbox;
    private $base_url = 'https://graph.microsoft.com/v1.0';

    /**
     * Construct Graph client. Settings may be omitted and will be resolved from
     * environment variables or WordPress options when not provided.
     *
     * Env vars (preferred):
     * - LGP_AZURE_TENANT_ID
     * - LGP_AZURE_CLIENT_ID
     * - LGP_AZURE_CLIENT_SECRET
     * - LGP_SHARED_MAILBOX
     * Options fallback:
     * - lgp_azure_tenant_id, lgp_azure_client_id, lgp_azure_client_secret, lgp_shared_mailbox
     *
     * @param array $settings Optional settings array.
     */
    public function __construct( $settings = array() )
    {
        $resolved            = $this->resolve_settings(is_array($settings) ? $settings : array());
        $this->tenant_id     = $resolved['tenant_id'] ?? '';
        $this->client_id     = $resolved['client_id'] ?? '';
        $this->client_secret = $resolved['client_secret'] ?? '';
        $this->mailbox       = $resolved['mailbox'] ?? '';
    }

    /**
     * Resolve configuration from provided settings, then env, then WP options.
     */
    private function resolve_settings( $settings )
    {
        // Prefer explicit settings
        $tenant_id     = $settings['tenant_id'] ?? null;
        $client_id     = $settings['client_id'] ?? null;
        $client_secret = $settings['client_secret'] ?? null;
        $mailbox       = $settings['mailbox'] ?? null;

        // Env fallback
        $tenant_id     = $tenant_id ?: getenv('LGP_AZURE_TENANT_ID');
        $client_id     = $client_id ?: getenv('LGP_AZURE_CLIENT_ID');
        $client_secret = $client_secret ?: getenv('LGP_AZURE_CLIENT_SECRET');
        $mailbox       = $mailbox ?: getenv('LGP_SHARED_MAILBOX');

        // Options fallback
        if (function_exists('get_option') ) {
            $tenant_id     = $tenant_id ?: get_option('lgp_azure_tenant_id');
            $client_id     = $client_id ?: get_option('lgp_azure_client_id');
            $client_secret = $client_secret ?: get_option('lgp_azure_client_secret');
            $mailbox       = $mailbox ?: get_option('lgp_shared_mailbox');
        }

        return array(
        'tenant_id'     => $tenant_id,
        'client_id'     => $client_id,
        'client_secret' => $client_secret,
        'mailbox'       => $mailbox,
        );
    }

    /**
     * Get app-only access token (cached via transient for ~50 minutes)
     */
    private function get_access_token()
    {
        $key   = 'lgp_graph_token_' . md5($this->tenant_id . $this->client_id);
        $token = get_transient($key);
        if ($token ) {
            return $token;
        }

        $response = wp_remote_post(
            'https://login.microsoftonline.com/' . $this->tenant_id . '/oauth2/v2.0/token',
            array(
            'body' => array(
                    'client_id'     => $this->client_id,
                    'client_secret' => $this->client_secret,
                    'scope'         => 'https://graph.microsoft.com/.default',
                    'grant_type'    => 'client_credentials',
            ),
            )
        );

        if (is_wp_error($response) ) {
            throw new Exception('Graph token request failed: ' . $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if ($code !== 200 || empty($body['access_token']) ) {
            throw new Exception('Graph token error: ' . wp_remote_retrieve_body($response));
        }

        set_transient($key, $body['access_token'], 50 * MINUTE_IN_SECONDS);
        return $body['access_token'];
    }

    private function request( $method, $path, $args = array() )
    {
        $token        = $this->get_access_token();
        $url          = $this->base_url . $path;
        $headers      = array(
        'Authorization' => 'Bearer ' . $token,
        'Content-Type'  => 'application/json',
        );
        $request_args = array_merge(
            array(
            'headers' => $headers,
            'timeout' => 20,
            ),
            $args
        );

        $response = wp_remote_request($url, array_merge($request_args, array( 'method' => $method )));
        if (is_wp_error($response) ) {
            throw new Exception('Graph request failed: ' . $response->get_error_message());
        }
        $status = wp_remote_retrieve_response_code($response);
        $body   = json_decode(wp_remote_retrieve_body($response), true);

        if ($status >= 400 ) {
            throw new Exception('Graph HTTP ' . $status . ': ' . wp_remote_retrieve_body($response));
        }

        return $body;
    }

    /**
     * Fetch messages (delta-capable). Provide $delta_token to continue sync.
     */
    public function get_messages( $delta_token = null )
    {
        $path = '/users/' . rawurlencode($this->mailbox) . '/mailFolders/Inbox/messages?$top=25&$select=id,subject,from,body,bodyPreview,receivedDateTime,hasAttachments,internetMessageId,conversationId,parentMessageId&$orderby=receivedDateTime DESC';
        if ($delta_token ) {
            // delta token is a full URL; call directly
            $body = $this->request('GET', str_replace($this->base_url, '', $delta_token));
        } else {
            $body = $this->request('GET', $path);
        }

        $messages = array();
        if (! empty($body['value']) ) {
            foreach ( $body['value'] as $m ) {
                $messages[] = array(
                'id'                => $m['id'],
                'subject'           => $m['subject'] ?? '',
                'from'              => $m['from']['emailAddress']['address'] ?? '',
                'from_name'         => $m['from']['emailAddress']['name'] ?? '',
                'internetMessageId' => $m['internetMessageId'] ?? '',
                'bodyPreview'       => $m['bodyPreview'] ?? '',
                'receivedDateTime'  => $m['receivedDateTime'] ?? '',
                'hasAttachments'    => $m['hasAttachments'] ?? false,
                'body'              => $m['body']['content'] ?? '',
                'contentType'       => $m['body']['contentType'] ?? 'Text',
                'conversationId'    => $m['conversationId'] ?? '',
                'parentMessageId'   => $m['parentMessageId'] ?? '',
                );
            }
        }

        $delta = $body['@odata.deltaLink'] ?? null;

        return array(
        'messages'    => $messages,
        'delta_token' => $delta,
        );
    }

    /**
     * Get attachments for a message
     */
    public function get_attachments( $message_id )
    {
        $path = '/users/' . rawurlencode($this->mailbox) . '/messages/' . rawurlencode($message_id) . '/attachments';
        $body = $this->request('GET', $path);
        return $body['value'] ?? array();
    }

    /**
     * Send mail via Graph
     */
    public function send_mail( $to, $subject, $html_body, $attachments = array() )
    {
        $graph_attachments = array();
        foreach ( $attachments as $att ) {
            if (empty($att['name']) || empty($att['contentBytes']) ) {
                continue;
            }
            $graph_attachments[] = array(
            '@odata.type'  => '#microsoft.graph.fileAttachment',
            'name'         => $att['name'],
            'contentBytes' => $att['contentBytes'],
            );
        }

        $payload = array(
        'message'         => array(
        'subject'      => $subject,
        'body'         => array(
                    'contentType' => 'HTML',
                    'content'     => $html_body,
        ),
        'toRecipients' => array(
        array( 'emailAddress' => array( 'address' => $to ) ),
        ),
        ),
        'saveToSentItems' => true,
        );

        if (! empty($graph_attachments) ) {
            $payload['message']['attachments'] = $graph_attachments;
        }

        $path = '/users/' . rawurlencode($this->mailbox) . '/sendMail';
        $this->request('POST', $path, array( 'body' => wp_json_encode($payload) ));
    }

    /**
     * Send mail message via shared mailbox (generic handler for complex payloads)
     *
     * @param  string $mailbox         Mailbox address
     * @param  array  $message_payload Full Graph message payload
     * @return array Response from Graph
     */
    public function send_mail_message( $mailbox, $message_payload )
    {
        $path = '/users/' . rawurlencode($mailbox) . '/sendMail';
        return $this->request('POST', $path, array( 'body' => wp_json_encode($message_payload) ));
    }

    /**
     * Get attachments with content for a message
     *
     * @param  string $message_id Message ID
     * @return array Attachments array
     */
    public function get_attachments_with_content( $message_id )
    {
        $path = '/users/' . rawurlencode($this->mailbox) . '/messages/' . rawurlencode($message_id) . '/attachments?$select=id,name,contentBytes,contentType';
        $body = $this->request('GET', $path);
        return $body['value'] ?? array();
    }

    /**
     * Get specific message by ID
     *
     * @param  string $message_id Message ID
     * @return array Message data
     */
    public function get_message( $message_id )
    {
        $path = '/users/' . rawurlencode($this->mailbox) . '/messages/' . rawurlencode($message_id) . '?$select=*';
        return $this->request('GET', $path);
    }

    /**
     * Mark message as read
     *
     * @param  string $message_id Message ID
     * @return bool Success
     */
    public function mark_as_read( $message_id )
    {
        $path = '/users/' . rawurlencode($this->mailbox) . '/messages/' . rawurlencode($message_id);
        $this->request('PATCH', $path, array( 'body' => wp_json_encode(array( 'isRead' => true )) ));
        return true;
    }

    /**
     * Get mailbox folder structure
     *
     * @return array Folders
     */
    public function get_folders()
    {
        $path = '/users/' . rawurlencode($this->mailbox) . '/mailFolders';
        $body = $this->request('GET', $path);
        return $body['value'] ?? array();
    }
}
