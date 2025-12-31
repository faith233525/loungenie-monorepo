<?php

/**
 * Canned Responses Manager
 * Template library for quick ticket replies
 *
 * @package LounGenie Portal
 * @since   1.9.0
 */

if (! defined('ABSPATH')) {
    exit;
}

class LGP_Canned_Responses
{

    /**
     * Get all canned responses
     *
     * @return array
     */
    public static function get_all()
    {
        return array(
            array(
                'id'       => 1,
                'title'    => 'Technician Dispatch',
                'category' => 'maintenance',
                'content'  => "Thank you for reporting this issue. We're dispatching a certified technician to your location. They will arrive within 24 hours to assess and resolve the problem.\n\nExpected arrival: [TIME]\nTechnician: [NAME]\n\nIf you have any questions, please don't hesitate to reach out.",
            ),
            array(
                'id'       => 2,
                'title'    => 'Battery Replacement',
                'category' => 'maintenance',
                'content'  => "We've identified that your LounGenie unit requires a battery replacement. This is a routine maintenance procedure.\n\nBattery Type: [MODEL]\nReplacement Date: [DATE]\nDuration: 30 minutes\n\nThe unit will remain operational during the replacement.",
            ),
            array(
                'id'       => 3,
                'title'    => 'Lock Malfunction - Immediate',
                'category' => 'critical',
                'content'  => "We understand the urgency of this lock malfunction issue. Our priority response team has been notified.\n\n🔴 CRITICAL PRIORITY\nResponse Time: Within 4 hours\nStatus: Technician en route\n\nEmergency Contact: 1-800-POOL-SAFE\n\nWe'll keep you updated every step of the way.",
            ),
            array(
                'id'       => 4,
                'title'    => 'Installation Scheduled',
                'category' => 'installation',
                'content'  => "Your new LounGenie unit installation has been scheduled!\n\n📅 Installation Details:\nDate: [DATE]\nTime: [TIME]\nDuration: 2-3 hours\nInstaller: [TECHNICIAN]\n\nPlease ensure the installation area is accessible. We'll send a reminder 24 hours before the appointment.",
            ),
            array(
                'id'       => 5,
                'title'    => 'Routine Maintenance Confirmation',
                'category' => 'maintenance',
                'content'  => "Your routine maintenance request has been received and logged.\n\n✅ Service Details:\nType: Preventive Maintenance\nSchedule: [DATE]\nEstimated Duration: 1 hour\n\nOur technician will perform:\n- Lock mechanism inspection\n- Battery check\n- Software update\n- Full system diagnostic\n\nNo downtime expected during maintenance.",
            ),
            array(
                'id'       => 6,
                'title'    => 'Issue Resolved',
                'category' => 'resolution',
                'content'  => "Great news! The issue with your LounGenie unit has been resolved.\n\n✅ Resolution Summary:\nIssue: [DESCRIPTION]\nAction Taken: [SOLUTION]\nCompleted: [DATE]\n\nYour unit is now fully operational. If you experience any further issues, please don't hesitate to open a new ticket.\n\nThank you for your patience!",
            ),
            array(
                'id'       => 7,
                'title'    => 'Information Request',
                'category' => 'general',
                'content'  => "Thank you for your inquiry. To better assist you, could you please provide the following information:\n\n• Unit Number: \n• Location: \n• Description of the issue: \n• When did this start: \n• Any error messages: \n\nOnce we receive this information, we'll be able to provide you with a more accurate solution.",
            ),
            array(
                'id'       => 8,
                'title'    => 'Warranty Information',
                'category' => 'general',
                'content'  => "Here's the warranty information for your LounGenie unit:\n\n📋 Warranty Coverage:\nUnit Model: [MODEL]\nPurchase Date: [DATE]\nWarranty Period: 24 months\nExpiration: [DATE]\n\nCovered:\n✅ Manufacturing defects\n✅ Lock mechanism failures\n✅ Electronic component malfunctions\n\nNot Covered:\n❌ Physical damage\n❌ Water damage\n❌ Unauthorized modifications\n\nFor warranty claims, please provide photos and a detailed description.",
            ),
            array(
                'id'       => 9,
                'title'    => 'Follow-Up Required',
                'category' => 'general',
                'content'  => "We're following up on your recent ticket to ensure everything is working properly.\n\nCould you please confirm:\n✓ Is the issue fully resolved?\n✓ Is the unit functioning normally?\n✓ Do you have any additional concerns?\n\nIf everything looks good, we'll close this ticket. Otherwise, please let us know and we'll continue working on it.",
            ),
            array(
                'id'       => 10,
                'title'    => 'Escalation to Engineering',
                'category' => 'critical',
                'content'  => "Your case has been escalated to our engineering team for specialized attention.\n\n🔧 Engineering Review:\nCase ID: [CASE_ID]\nAssigned Engineer: [NAME]\nReview Priority: High\n\nOur engineering team will:\n1. Conduct a detailed analysis\n2. Identify root cause\n3. Develop a custom solution\n4. Implement and test the fix\n\nEstimated Resolution: 48-72 hours\n\nWe'll provide daily updates on progress.",
            ),
        );
    }

    /**
     * Get canned response by ID
     *
     * @param  int $id Response ID
     * @return array|null
     */
    public static function get_by_id($id)
    {
        $responses = self::get_all();
        foreach ($responses as $response) {
            if ($response['id'] === $id) {
                return $response;
            }
        }
        return null;
    }

    /**
     * Get responses by category
     *
     * @param  string $category Category name
     * @return array
     */
    public static function get_by_category($category)
    {
        $responses = self::get_all();
        return array_filter(
            $responses,
            function ($response) use ($category) {
                return $response['category'] === $category;
            }
        );
    }

    /**
     * Search canned responses
     *
     * @param  string $query Search query
     * @return array
     */
    public static function search($query)
    {
        $responses = self::get_all();
        $query     = strtolower($query);

        return array_filter(
            $responses,
            function ($response) use ($query) {
                return strpos(strtolower($response['title']), $query) !== false ||
                    strpos(strtolower($response['content']), $query) !== false;
            }
        );
    }
}
