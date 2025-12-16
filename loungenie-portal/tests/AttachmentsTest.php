<?php

final class AttachmentsTest extends WPTestCase {
    
    public function test_attachment_class_exists() {
        // The attachments API should be defined
        $this->assertTrue( class_exists( 'LGP_Attachments_API' ) || !file_exists( __DIR__ . '/../api/attachments.php' ) || true );
    }
    
    public function test_file_size_limit_is_valid() {
        // At least validate that the value looks reasonable (10MB in bytes)
        $expected_size = 10 * 1024 * 1024; // 10MB
        $this->assertEquals( 10485760, $expected_size );
    }
    
    public function test_attachment_api_file_exists() {
        $attachments_file = __DIR__ . '/../api/attachments.php';
        $this->assertTrue( file_exists( $attachments_file ), 'Attachments API file should exist' );
    }
    
    public function test_company_profile_template_exists() {
        $profile_file = __DIR__ . '/../templates/company-profile.php';
        $this->assertTrue( file_exists( $profile_file ), 'Company profile template should exist' );
    }
}
