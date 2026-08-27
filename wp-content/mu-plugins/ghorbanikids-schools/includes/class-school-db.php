<?php
/**
 * Class GK_School_DB
 * Handles database schema creation and data queries for Schools & Kindergartens
 */
if (!defined('ABSPATH')) exit;

class GK_School_DB {

    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // 1. Organizations table
        $table_orgs = $wpdb->prefix . 'gk_organizations';
        $sql_orgs = "CREATE TABLE IF NOT EXISTS $table_orgs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            logo_url text DEFAULT NULL,
            phone varchar(50) DEFAULT NULL,
            city varchar(100) DEFAULT NULL,
            manager_user_id bigint(20) NOT NULL,
            student_limit int(11) DEFAULT 30,
            expires_at datetime DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY manager_user_id (manager_user_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_orgs);

        // 2. Classes table
        $table_classes = $wpdb->prefix . 'gk_classes';
        $sql_classes = "CREATE TABLE IF NOT EXISTS $table_classes (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            org_id bigint(20) NOT NULL,
            name varchar(255) NOT NULL,
            invite_code varchar(64) NOT NULL,
            teacher_user_id bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY invite_code (invite_code),
            KEY org_id (org_id),
            KEY teacher_user_id (teacher_user_id)
        ) $charset_collate;";
        dbDelta($sql_classes);

        // 3. Students table
        $table_students = $wpdb->prefix . 'gk_students';
        $sql_students = "CREATE TABLE IF NOT EXISTS $table_students (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            org_id bigint(20) NOT NULL,
            class_id bigint(20) NOT NULL,
            name varchar(255) NOT NULL,
            age int(3) DEFAULT 5,
            parent_phone varchar(50) DEFAULT NULL,
            student_token varchar(64) NOT NULL,
            total_game_score int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY student_token (student_token),
            KEY org_id (org_id),
            KEY class_id (class_id)
        ) $charset_collate;";
        dbDelta($sql_students);

        // 4. Student assessments mapping
        $table_ass = $wpdb->prefix . 'gk_student_assessments';
        $sql_ass = "CREATE TABLE IF NOT EXISTS $table_ass (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            student_id bigint(20) NOT NULL,
            org_id bigint(20) NOT NULL,
            class_id bigint(20) NOT NULL,
            assessment_slug varchar(100) NOT NULL,
            result_id bigint(20) NOT NULL,
            completed_by varchar(50) DEFAULT 'parent',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY student_id (student_id),
            KEY org_id (org_id),
            KEY class_id (class_id),
            KEY result_id (result_id)
        ) $charset_collate;";
        dbDelta($sql_ass);
    }
}