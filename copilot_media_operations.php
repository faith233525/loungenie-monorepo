<?php

/**
 * LounGenie Media Operations – One-shot mu-plugin
 * Permanently deletes 30 duplicate attachments + updates 186 items' alt/title.
 * Token-gated: requires ?copilot_run=media_ops_run_7z9k
 * Self-deletes after successful execution.
 */

if (! defined('ABSPATH')) {
    return;
}
if (! isset($_GET['copilot_run']) || $_GET['copilot_run'] !== 'media_ops_run_7z9k') {
    return;
}

add_action('wp_loaded', 'copilot_media_ops_execute');

function copilot_media_ops_execute()
{

    $results = array('deletes' => array(), 'updates' => array());

    // ── DUPLICATE DELETES (30) ──────────────────────────────────────────────
    $delete_ids = array(
        1703,
        1710,
        1752,
        1819,
        1858,
        2028,
        2032,
        2033,
        2038,
        2044,
        2045,
        2056,
        2057,
        2058,
        2061,
        2062,
        2063,
        2066,
        2067,
        2072,
        2090,
        2365,
        2366,
        3540,
        3546,
        3885,
        4421,
        5019,
        5036,
        9624,
    );
    $delete_set = array_flip($delete_ids);

    foreach ($delete_ids as $att_id) {
        $r = wp_delete_attachment((int) $att_id, true);
        $results['deletes'][] = array(
            'id'     => $att_id,
            'status' => ($r ? 'deleted' : 'not_found'),
        );
    }

    // ── METADATA UPDATES (186) ─────────────────────────────────────────────
    // 't' = new post_title (empty string = skip title update)
    // 'a' = new _wp_attachment_image_alt (empty string = skip alt update)
    $update_list = array(
        array('id' => 9629, 't' => '', 'a' => 'LounGenie poolside hospitality photo'),
        array('id' => 9628, 't' => '', 'a' => 'LounGenie poolside hospitality photo'),
        array('id' => 9627, 't' => '', 'a' => 'LounGenie poolside hospitality photo'),
        array('id' => 9626, 't' => '', 'a' => 'LounGenie poolside hospitality photo'),
        array('id' => 9625, 't' => '', 'a' => 'LounGenie poolside hospitality photo'),
        array('id' => 9624, 't' => '', 'a' => 'LounGenie poolside hospitality photo'), // also in deletes – will be skipped
        array('id' => 9623, 't' => '', 'a' => 'LounGenie poolside hospitality photo'),
        array('id' => 9622, 't' => '', 'a' => 'LounGenie poolside hospitality photo'),
        array('id' => 9621, 't' => '', 'a' => 'LounGenie poolside hospitality photo'),
        array('id' => 9620, 't' => '', 'a' => 'LounGenie poolside hospitality photo'),
        array('id' => 9619, 't' => '', 'a' => 'LounGenie poolside hospitality photo'),
        array('id' => 9618, 't' => '', 'a' => 'LounGenie poolside hospitality photo'),
        array('id' => 9617, 't' => '', 'a' => 'LounGenie poolside hospitality photo'),
        array('id' => 9616, 't' => '', 'a' => 'LounGenie poolside hospitality photo'),
        array('id' => 9615, 't' => '', 'a' => 'LounGenie poolside hospitality photo'),
        array('id' => 8378, 't' => '', 'a' => 'Hilton Wakoloa Village 2018 10 Aloha Falls Cabana 1'),
        array('id' => 8370, 't' => '', 'a' => '298872056 10158994266838325 2480796936934944436 n'),
        array('id' => 8367, 't' => '', 'a' => '200990133 10226280561100384 1892995075070127113 n'),
        array('id' => 8366, 't' => '', 'a' => 'LounGenie poolside hospitality photo'),
        array('id' => 8365, 't' => '', 'a' => 'LounGenie poolside hospitality photo'),
        array('id' => 8361, 't' => '', 'a' => 'LounGenie poolside hospitality photo'),
        array('id' => 8168, 't' => '', 'a' => 'placeholder'),
        array('id' => 7249, 't' => 'LounGenie poolside hospitality photo', 'a' => ''),
        array('id' => 7240, 't' => '', 'a' => 'LounGenie poolside hospitality photo'),
        array('id' => 7235, 't' => '', 'a' => 'LounGenie poolside hospitality photo'),
        array('id' => 7234, 't' => '', 'a' => 'LounGenie poolside hospitality photo'),
        array('id' => 7233, 't' => '', 'a' => 'LounGenie poolside hospitality photo'),
        array('id' => 7158, 't' => 'nano banana pro show the full unit in a premium poolside beach setting', 'a' => 'nano banana pro show the full unit in a premium poolside beach setting'),
        array('id' => 7042, 't' => '', 'a' => 'avbcmjmh2'),
        array('id' => 6963, 't' => '', 'a' => 'image dcabb38d'),
        array('id' => 6950, 't' => '', 'a' => 'aa74e14a 2a46 4ce9 93e8 d3d4ca832bc6'),
        array('id' => 6945, 't' => '', 'a' => 'Gemini Generated Image fzkd20fzkd20fzkd (1)'),
        array('id' => 6841, 't' => '', 'a' => 'text quotes'),
        array('id' => 6182, 't' => '', 'a' => 'Pool Safe Notice of Meeting Combined with MIC'),
        array('id' => 6181, 't' => '', 'a' => 'Pool Safe Form of Proxy Common Shares &#8211; Final'),
        array('id' => 5922, 't' => '', 'a' => 'Photorealistic luxury resort scene with ultra realistic lighting materials reflections and shadow 1 (1)'),
        array('id' => 5598, 't' => '', 'a' => 'David Deacon Joins PSI Board (Final) &#8211; 03 02 2026'),
        array('id' => 4423, 't' => 'gemini generated image c86aj8c86aj8c86a', 'a' => 'gemini generated image c86aj8c86aj8c86a'),
        array('id' => 4035, 't' => '', 'a' => '2026 LounGenie Fact Sheet'),
        array('id' => 4000, 't' => '', 'a' => '2025 Report on Fighting Against Forced Labour &#8211; Pool Safe Inc'),
        array('id' => 3831, 't' => '', 'a' => 'PSI Announces Closing of Sr'),
        array('id' => 3790, 't' => '', 'a' => 'PSI Announces Auditors Appointment (Final) &#8211; 02 25 2025'),
        array('id' => 3787, 't' => '', 'a' => 'PSI Announces Closing of Extension of Sr'),
        array('id' => 3786, 't' => '', 'a' => 'Pool Safe 2023 Deb Financing PR Final 04 17 2023 1 1'),
        array('id' => 3784, 't' => '', 'a' => 'PSI Announces Auditors Appointment final 09 25 2024'),
        array('id' => 3778, 't' => '', 'a' => 'Pool Safe Credit Amendment PP News Release Sedar 06 02 2025'),
        array('id' => 3777, 't' => '', 'a' => 'PSI Repays Promissory Note Debentures 05 23 2025'),
        array('id' => 3776, 't' => '', 'a' => 'PSI Announces Short Term Promissory Note Final 03 19 2025'),
        array('id' => 3775, 't' => '', 'a' => 'PSI Announces Debenture Warrant Extension Final 12 19 2024 1'),
        array('id' => 3774, 't' => '', 'a' => 'PSI Announces Retirement of Debentures Warrant Exercise 12 30 2024 3'),
        array('id' => 3632, 't' => '', 'a' => 'PSI Vegas Resort Rev Share PR final 03 26 2019'),
        array('id' => 3631, 't' => '', 'a' => 'PSI Pounder Update on QT PR 12 06 2016'),
        array('id' => 3630, 't' => '', 'a' => 'PSI Pounder Complete QT PR 04 19 2017'),
        array('id' => 3629, 't' => '', 'a' => 'PSI Intrexa Credit Facility PR final 04 02 2019'),
        array('id' => 3628, 't' => '', 'a' => 'PSI IMLLC DA PR 09 18 2017'),
        array('id' => 3627, 't' => '', 'a' => 'PSI Gaylord Rev Share final 10 03 2018'),
        array('id' => 3626, 't' => '', 'a' => 'PSI CFG mCloud Partnership final 05 17 2018'),
        array('id' => 3625, 't' => '', 'a' => 'PSI Sells Units to Norwegian final June 2019'),
        array('id' => 3624, 't' => '', 'a' => 'PSI Sells to Maui Jacks final 05 27 2019'),
        array('id' => 3623, 't' => '', 'a' => 'PSI Retains Frontier PR 06 02 2017'),
        array('id' => 3622, 't' => '', 'a' => 'PSI Partners with Ravine final 05 30 2019'),
        array('id' => 3621, 't' => '', 'a' => 'PSI Mngmt BoD ChangesPR 03 07 2017'),
        array('id' => 3620, 't' => '', 'a' => 'PSI MENA Signs Contracts final 10 11 2018'),
        array('id' => 3619, 't' => '', 'a' => 'PSI MENA Distribution Agmt PR 04 06 2017'),
        array('id' => 3618, 't' => '', 'a' => 'PSI Alawwal EDA PR final 08 28 2017'),
        array('id' => 3617, 't' => '', 'a' => 'PSI Grants Options PR 06 02 2017'),
        array('id' => 3616, 't' => '', 'a' => 'PSI Enters Florida Market 83 Units final 05 23 2019'),
        array('id' => 3615, 't' => '', 'a' => 'PSI Engages Mackie PR 05 01 2017'),
        array('id' => 3614, 't' => '', 'a' => 'PSI Credit Amendment Financing PR final 07 13 2022'),
        array('id' => 3613, 't' => '', 'a' => 'PSI Closes First Tranche Equity Financing final 09 27 2018'),
        array('id' => 3612, 't' => '', 'a' => 'PSI Closes First Tranche Deb Financing final 02 13 2018'),
        array('id' => 3611, 't' => '', 'a' => 'PSI Arranges Conv Deb Financing May 2018'),
        array('id' => 3610, 't' => '', 'a' => 'PSI Announces Financing Debt Conversion 04 26 2021'),
        array('id' => 3609, 't' => '', 'a' => 'PSI Announces Close of Conv Deb Financing 06 21 2018'),
        array('id' => 3608, 't' => '', 'a' => 'PSI Announces Changes to Board G'),
        array('id' => 3607, 't' => '', 'a' => 'PSI Announces Changes to Board final Aug 2018'),
        array('id' => 3606, 't' => '', 'a' => 'PSI Announces Changes to Board final 03 14 2018'),
        array('id' => 3605, 't' => '', 'a' => 'PSI Named Approved Supplier with Avendra (Final) &#8211; 07 26 2023'),
        array('id' => 3604, 't' => '', 'a' => 'PSI Closes Debt Conversion Extension of Sr'),
        array('id' => 3603, 't' => '', 'a' => 'PSI Announces Master Service Agmt (final) &#8211; 03 05 2024 2'),
        array('id' => 3602, 't' => '', 'a' => 'PSI Announces Loungenie Rebranding (final) &#8211; 02 23 2023'),
        array('id' => 3601, 't' => '', 'a' => 'Pool Safe Cowabunga Rev Share Agmt'),
        array('id' => 3600, 't' => '', 'a' => 'Nils Kravis Joins PSI Board (final) &#8211; 08 30 2022'),
        array('id' => 3599, 't' => '', 'a' => 'Pool Safe Partner Opens Dubai Office final 02 15 2018'),
        array('id' => 3598, 't' => '', 'a' => 'Pool Safe Credit Debt Amendments Final PR Dec 2020'),
        array('id' => 3597, 't' => '', 'a' => 'Pool Safe Arranges Debenture Financing'),
        array('id' => 3596, 't' => '', 'a' => 'Pool Grants Stock Options 04 11 2018'),
        array('id' => 3595, 't' => '', 'a' => 'Pool Safe Upsizes Debenture Financing &#8211; 06 27 2023'),
        array('id' => 3594, 't' => '', 'a' => 'Pool Safe Press Release re Closing of the Final Tranche (Final) &#8211; 11 10 2022'),
        array('id' => 3593, 't' => '', 'a' => 'Pool Safe Press Release re Closing First Tranche (final) &#8211; 08 31 2022'),
        array('id' => 3592, 't' => '', 'a' => 'Pool Safe PR re Closing Tranche 1 (final) &#8211; 06 01 2023'),
        array('id' => 3591, 't' => '', 'a' => 'Pool Safe PR Closing Debenture Financing (Final) &#8211; 07 06 2023'),
        array('id' => 3590, 't' => '', 'a' => 'Pool Safe Announces 2023 AGM Results (final) &#8211; 03 01 2023'),
        array('id' => 3572, 't' => '', 'a' => 'LounGenie Fact Sheet'),
        array('id' => 3571, 't' => '', 'a' => 'Pool Safe Inc Loungenie Brochure'),
        array('id' => 3567, 't' => '', 'a' => "Financials \xe2\x80\x93 September 30, 2025"),
        array('id' => 3566, 't' => '', 'a' => "MD&#038;A \xe2\x80\x93 September 30, 2025"),
        array('id' => 3453, 't' => '', 'a' => 'Untitled design 16 scaled'),
        array('id' => 3066, 't' => 'untitled design 11', 'a' => 'untitled design 11'),
        array('id' => 3050, 't' => '', 'a' => 'The LounGenie Insulated Ice Bucket'),
        array('id' => 3043, 't' => '', 'a' => 'Westin Hilton Head &#8211; Fatima Abdi 2025 (2)'),
        array('id' => 3042, 't' => '', 'a' => 'Westin Hilton Head &#8211; Fatima Abdi 2025 (2)'),
        array('id' => 3020, 't' => '', 'a' => 'Westin Hilton Head &#8211; Fatima Abdi 2025'),
        array('id' => 2546, 't' => '', 'a' => 'Q4 YE 2016 Audited Financials Pool Safe'),
        array('id' => 2545, 't' => '', 'a' => 'YE 2016 MDA Pool Safe'),
        array('id' => 2544, 't' => '', 'a' => 'Q3 2016 Financials Pool Safe'),
        array('id' => 2542, 't' => '', 'a' => 'PSI Q2 2017 Financials Sedar June 30 2017 1'),
        array('id' => 2541, 't' => '', 'a' => 'PSI Q2 2017 MDA Sedar June 30 2017'),
        array('id' => 2540, 't' => '', 'a' => 'Q1 2017 Financials Pool Safe'),
        array('id' => 2539, 't' => '', 'a' => 'Q1 2017 MDA Pool Safe'),
        array('id' => 2538, 't' => '', 'a' => 'PSI Q3 2017 Financials Sedar Sept 30 2017'),
        array('id' => 2537, 't' => '', 'a' => 'PSI Q3 2017 MDA Sedar Sept 30 2017'),
        array('id' => 2535, 't' => '', 'a' => 'PSI 2017 YE Financials Final'),
        array('id' => 2534, 't' => '', 'a' => 'PSI 2017 YE MDA Sedar Dec 31 2017'),
        array('id' => 2532, 't' => '', 'a' => 'Pool Safe Financials final Mar 31 2018'),
        array('id' => 2531, 't' => '', 'a' => 'Pool Safe MDA final Mar 31 2018'),
        array('id' => 2529, 't' => '', 'a' => 'Pool Safe Financials final June 30 2018'),
        array('id' => 2528, 't' => '', 'a' => 'Pool Safe MDA final June 30 2018'),
        array('id' => 2526, 't' => '', 'a' => 'Pool Safe MDA Sedar September 30 2018'),
        array('id' => 2525, 't' => '', 'a' => 'Pool Safe Financials Sedar September 30 2018'),
        array('id' => 2524, 't' => '', 'a' => 'PSI 2018 YE MDA Sedar Dec 31 2018'),
        array('id' => 2523, 't' => '', 'a' => 'PSI 2018 YE Financials Sedar Dec 31 2018'),
        array('id' => 2520, 't' => '', 'a' => 'Pool Safe MDA Sedar March 31 2019'),
        array('id' => 2519, 't' => '', 'a' => 'Pool Safe Financials Sedar March 31 2019'),
        array('id' => 2517, 't' => '', 'a' => 'Pool Safe MDA Sedar June 30 2019'),
        array('id' => 2516, 't' => '', 'a' => 'Pool Safe Financials Sedar June 30 2019'),
        array('id' => 2514, 't' => '', 'a' => 'Pool Safe MDA Sedar Sept 30 2019'),
        array('id' => 2513, 't' => '', 'a' => 'Pool Safe Financials Sedar Sept 30 2019'),
        array('id' => 2511, 't' => '', 'a' => 'PSI 2019 YE MDA Sedar Dec 31 2019'),
        array('id' => 2510, 't' => '', 'a' => 'PSI 2019 YE Financials Sedar Dec 31 2019'),
        array('id' => 2507, 't' => '', 'a' => 'Pool Safe Financials Q1 2020 Sedar March 31 2020'),
        array('id' => 2506, 't' => '', 'a' => 'Pool Safe MDA Sedar March 31 2020'),
        array('id' => 2504, 't' => '', 'a' => 'Pool Safe MDA Sedar June 30 2020'),
        array('id' => 2503, 't' => '', 'a' => 'Pool Safe Financials Q2 2020 Sedar June 30 2020'),
        array('id' => 2501, 't' => '', 'a' => 'Pool Safe MDA Sedar September 30 2020'),
        array('id' => 2500, 't' => '', 'a' => 'Pool Safe Financials Q3 2020 Sedar September 30 2020'),
        array('id' => 2498, 't' => '', 'a' => 'Pool Safe 2020 Q4 YE Audited MDA SEDAR'),
        array('id' => 2497, 't' => '', 'a' => 'Pool Safe 2020 Q4 YE Audited Financials SEDAR'),
        array('id' => 2494, 't' => '', 'a' => 'Pool Safe MDA Q1 2021 Sedar'),
        array('id' => 2493, 't' => '', 'a' => 'Pool Safe Financials Q1 2021 Sedar'),
        array('id' => 2491, 't' => '', 'a' => 'Pool Safe MDA Q2 2021 Sedar'),
        array('id' => 2490, 't' => '', 'a' => 'Pool Safe Financials Q2 2021 Sedar'),
        array('id' => 2489, 't' => '', 'a' => 'Pool Safe MDA Q3 2021 Sedar'),
        array('id' => 2488, 't' => '', 'a' => 'Pool Safe Financials Q3 2021 Sedar'),
        array('id' => 2486, 't' => '', 'a' => 'Pool Safe 2021 Q4 YE MDA SEDAR'),
        array('id' => 2485, 't' => '', 'a' => 'Pool Safe 2021 Q4 YE Audited Financials SEDAR'),
        array('id' => 2483, 't' => '', 'a' => 'Pool Safe 2022 Q1 MDA SEDAR'),
        array('id' => 2482, 't' => '', 'a' => 'Pool Safe 2022 Q1 Financials SEDAR'),
        array('id' => 2480, 't' => '', 'a' => 'Pool Safe 2022 Q2 MDA SEDAR 1'),
        array('id' => 2479, 't' => '', 'a' => 'Pool Safe 2022 Q2 Financials SEDAR'),
        array('id' => 2477, 't' => '', 'a' => 'Pool Safe MDA Q3 2022 SEDAR'),
        array('id' => 2476, 't' => '', 'a' => 'Pool Safe 2022 Q3 Financials SEDAR'),
        array('id' => 2474, 't' => '', 'a' => 'Pool Safe 2022 Q4 YE MDA Sedar'),
        array('id' => 2473, 't' => '', 'a' => 'Pool Safe 2022 Q4 YE Financials Sedar'),
        array('id' => 2467, 't' => '', 'a' => 'Pool Safe MDA Q1 2023 Sedar'),
        array('id' => 2466, 't' => '', 'a' => 'Pool Safe Financials Q1 2023 Sedar'),
        array('id' => 2463, 't' => '', 'a' => 'Pool Safe MDA Q2 2023 Sedar'),
        array('id' => 2462, 't' => '', 'a' => 'Pool Safe Financials Q2 2023 Sedar'),
        array('id' => 2460, 't' => '', 'a' => 'Pool Safe MDA Q3 2023 Sedar 1'),
        array('id' => 2459, 't' => '', 'a' => 'Pool Safe Financials Q3 2023 Sedar'),
        array('id' => 2457, 't' => '', 'a' => 'Pool Safe 2023 Q4 YE MDA Sedar'),
        array('id' => 2456, 't' => '', 'a' => 'Pool Safe 2023 Q4 YE Financials Sedar 1'),
        array('id' => 2454, 't' => '', 'a' => 'Pool Safe MDA Q1 2024 Sedar'),
        array('id' => 2453, 't' => '', 'a' => 'Pool Safe Financials Q1 2024 Sedar'),
        array('id' => 2451, 't' => '', 'a' => 'Pool Safe MDA Q2 2024 Sedar 06 30 2024 1'),
        array('id' => 2450, 't' => '', 'a' => 'Pool Safe Financials Q2 2024 Sedar 06 30 2024 1'),
        array('id' => 2448, 't' => '', 'a' => 'Pool Safe MDA Q3 2024 Sedar 09 30 2024 1'),
        array('id' => 2447, 't' => '', 'a' => 'Pool Safe Financials Q3 2024 Sedar 09 30 2024 1'),
        array('id' => 2444, 't' => '', 'a' => 'Pool Safe 2024 Q4 YE MDA Sedar 04 30 2025'),
        array('id' => 2443, 't' => '', 'a' => 'Pool Safe 2024 Q4 YE Financials Sedar 04 30 2025'),
        array('id' => 2440, 't' => '', 'a' => 'Pool Safe Financial Statement Request Form 2025 AGM Sedar'),
        array('id' => 2438, 't' => '', 'a' => 'Pool Safe Form of Proxy Common Shares 2025 AGM Sedar'),
        array('id' => 2436, 't' => '', 'a' => 'Pool Safe MIC re 2025 AGM Final Sedar'),
        array('id' => 2434, 't' => '', 'a' => 'Notice of Meeting Pool Safe AGSM 2025'),
        array('id' => 2432, 't' => '', 'a' => 'Pool Safe MDA Q1 2025 Sedar'),
        array('id' => 2430, 't' => '', 'a' => 'Pool Safe Financials Q1 2025 Sedar'),
        array('id' => 2429, 't' => '', 'a' => 'Pool Safe MDA Q2 2025 Sedar'),
        array('id' => 2428, 't' => '', 'a' => 'Pool Safe Financials Q2 2025 Sedar'),
        array('id' => 1983, 't' => '', 'a' => 'Untitled design (17)'),
        array('id' => 1982, 't' => '', 'a' => 'LounGenie'),
        array('id' => 1981, 't' => '', 'a' => 'Untitled design (17)'),
        array('id' => 1946, 't' => '', 'a' => 'LounGenie poolside hospitality photo'),
        array('id' => 1945, 't' => '', 'a' => 'LounGenie poolside hospitality photo'),
        array('id' => 1818, 't' => '1720098866562 1', 'a' => ''),
        array('id' => 1613, 't' => '', 'a' => 'Adobe Express &#8211; Villatel (4) (1)'),
        array('id' => 1612, 't' => '', 'a' => 'Adobe Express &#8211; Villatel (4)'),
    );

    foreach ($update_list as $item) {
        $id  = (int) $item['id'];
        $out = array('id' => $id, 'title_updated' => false, 'alt_updated' => false, 'status' => 'ok');

        // Skip IDs that were just permanently deleted
        if (isset($delete_set[$id])) {
            $out['status'] = 'skipped_deleted';
            $results['updates'][] = $out;
            continue;
        }

        if (! empty($item['t'])) {
            $r = wp_update_post(array('ID' => $id, 'post_title' => $item['t']), true);
            $out['title_updated'] = (! is_wp_error($r) && $r > 0);
        }

        if (! empty($item['a'])) {
            update_post_meta($id, '_wp_attachment_image_alt', $item['a']);
            $out['alt_updated'] = true;
        }

        if (empty($item['t']) && empty($item['a'])) {
            $out['status'] = 'nothing_to_do';
        }

        $results['updates'][] = $out;
    }

    // Write results to uploads dir (readable via HTTP for retrieval)
    $json_path = WP_CONTENT_DIR . '/uploads/media_ops_results.json';
    file_put_contents($json_path, json_encode($results, JSON_PRETTY_PRINT));

    // Self-delete before output so any error below leaves no plugin residue
    @unlink(__FILE__);

    // Respond with JSON and terminate
    nocache_headers();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($results, JSON_PRETTY_PRINT);
    exit;
}
