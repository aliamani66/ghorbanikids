<?php
/**
 * Class GK_Scoring_Engine
 * Handles calculation, normalization and recommendation generation
 */
if (!defined('ABSPATH')) exit;

class GK_Scoring_Engine {

    public static function init() {
        add_action('wp_ajax_gk_submit_assessment', [__CLASS__, 'handle_ajax_submission']);
        add_action('wp_ajax_nopriv_gk_submit_assessment', [__CLASS__, 'handle_ajax_submission']);
    }

    public static function handle_ajax_submission() {
        check_ajax_referer('gk_assessment_nonce', 'nonce');

        $slug       = isset($_POST['slug']) ? sanitize_text_field($_POST['slug']) : '';
        $child_name = isset($_POST['child_name']) ? sanitize_text_field($_POST['child_name']) : 'فرزند عزیز';
        $child_age  = isset($_POST['child_age']) ? sanitize_text_field($_POST['child_age']) : '';
        $answers    = isset($_POST['answers']) && is_array($_POST['answers']) ? $_POST['answers'] : [];

        $test_data = GK_Assessment_CPT::get_test_by_slug($slug);
        if (!$test_data) {
            wp_send_json_error(['message' => 'تست مورد نظر نامعتبر است.']);
        }

        // Calculate scores per category
        $cat_scores = [];
        $cat_max    = [];

        foreach ($test_data['categories'] as $cat_key => $cat_info) {
            $cat_scores[$cat_key] = 0;
            $cat_max[$cat_key]    = 0;
        }

        // Find max option value in this test
        $option_keys = array_keys($test_data['options']);
        $max_opt_val = max($option_keys);

        foreach ($test_data['questions'] as $q) {
            $qid = $q['id'];
            $cat = $q['cat'];
            $val = isset($answers[$qid]) ? intval($answers[$qid]) : 0;

            if (isset($cat_scores[$cat])) {
                $cat_scores[$cat] += $val;
                $cat_max[$cat]    += $max_opt_val;
            }
        }

        // Compute percentages and normalized data
        $summary = [];
        $chart_labels = [];
        $chart_data   = [];
        $chart_colors = [];

        foreach ($cat_scores as $cat_key => $score) {
            $max = $cat_max[$cat_key] > 0 ? $cat_max[$cat_key] : 1;
            $pct = round(($score / $max) * 100);
            $cat_info = $test_data['categories'][$cat_key];

            $summary[$cat_key] = [
                'name'            => $cat_info['name'],
                'icon'            => $cat_info['icon'],
                'color'           => $cat_info['color'],
                'score'           => $score,
                'max'             => $max,
                'percentage'      => $pct,
                'desc'            => $cat_info['desc'],
                'recommend_games' => $cat_info['recommend_games']
            ];

            $chart_labels[] = $cat_info['name'];
            $chart_data[]   = $pct;
            $chart_colors[] = $cat_info['color'];
        }

        // Sort to find top strengths
        $sorted_summary = $summary;
        uasort($sorted_summary, function($a, $b) {
            return $b['percentage'] <=> $a['percentage'];
        });

        // Collect recommended games
        $recommended_game_slugs = [];
        foreach ($sorted_summary as $k => $v) {
            foreach ($v['recommend_games'] as $g_slug) {
                if (!in_array($g_slug, $recommended_game_slugs)) {
                    $recommended_game_slugs[] = $g_slug;
                }
            }
            if (count($recommended_game_slugs) >= 4) break;
        }

        // Save result in DB
        global $wpdb;
        $table = $wpdb->prefix . 'gk_assessment_results';
        $user_id = get_current_user_id();

        $wpdb->insert($table, [
            'user_id'         => $user_id,
            'assessment_slug' => $slug,
            'child_name'      => $child_name,
            'child_age'       => $child_age,
            'scores_data'     => wp_json_encode($summary),
            'answers_data'    => wp_json_encode($answers),
            'recommendations' => wp_json_encode($recommended_game_slugs),
            'created_at'      => current_time('mysql')
        ]);

        $result_id = $wpdb->insert_id;

        // Render report HTML
        $report_html = GK_Report_Renderer::render_report_card([
            'result_id'         => $result_id,
            'slug'              => $slug,
            'test_title'        => $test_data['title'],
            'child_name'        => $child_name,
            'child_age'         => $child_age,
            'summary'           => $summary,
            'sorted_summary'    => $sorted_summary,
            'recommended_games' => $recommended_game_slugs,
            'chart_labels'      => $chart_labels,
            'chart_data'        => $chart_data,
            'chart_colors'      => $chart_colors
        ]);

        wp_send_json_success([
            'report_html'  => $report_html,
            'chart_labels' => $chart_labels,
            'chart_data'   => $chart_data,
            'chart_colors' => $chart_colors,
            'result_id'    => $result_id
        ]);
    }
}