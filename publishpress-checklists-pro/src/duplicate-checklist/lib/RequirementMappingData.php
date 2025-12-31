<?php

/**
 * @package     PublishPress\ChecklistsPro
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\ChecklistsPro\DuplicateChecklist;

/**
 * Class RequirementMappingData
 * 
 * Contains static mapping data for requirements
 */
class RequirementMappingData
{
    /**
     * Map of requirement names to their display titles
     */
    const TITLE_MAPPING = [
        'words_count' => 'Words Count',
        'title_count' => 'Title Count',
        'featured_image_alt' => 'Featured Image Alt',
        'featured_image' => 'Featured Image',
        'internal_links' => 'Internal Links',
        'external_links' => 'External Links',
        'required_tags' => 'Required Tags',
        'required_categories' => 'Required Categories',
        'image_alt' => 'All images have Alt text',
        'filled_excerpt' => 'Filled Excerpt',
        'approved_by' => 'Approved by a user in this role',
        'categories_count' => 'Categories Count',
        'tags_count' => 'Tags Count',
        'validate_links' => 'Validate Links',
        'featured_image_caption' => 'Featured Image Caption',
        'image_alt_count' => 'Image Alt Count',
        'prohibited_categories' => 'Prohibited Categories',
        'prohibited_tags' => 'Prohibited Tags',
        'featured_image_width' => 'Featured Image Width',
        'featured_image_height' => 'Featured Image Height',
        'image_count' => 'Image Count',
        'audio_count' => 'Audio Count',
        'video_count' => 'Video Count',
        'approved_by_user' => 'Approved By User',
        'no_heading_tags' => 'No Heading Tags',
        'table_header' => 'Table Header',
        'heading_in_hierarchy' => 'Heading In Hierarchy',
        'single_h1_per_page' => 'Single H1 Per Page',
        'publish_time_exact' => 'Publish Time Exact',
        'publish_time_future' => 'Publish Time Future',
        'rank_math_score' => 'Rank Math Score',
    ];

    /**
     * Map of requirement names to their class names
     */
    const CLASS_MAPPING = [
        'approved_by' => '\\PublishPress\\Checklists\\Core\\Requirement\\Approved_by',
        'categories_count' => '\\PublishPress\\Checklists\\Core\\Requirement\\Categories_count',
        'external_links' => '\\PublishPress\\Checklists\\Core\\Requirement\\External_links',
        'featured_image_alt' => '\\PublishPress\\Checklists\\Core\\Requirement\\Featured_image_alt',
        'featured_image_caption' => '\\PublishPress\\Checklists\\Core\\Requirement\\Featured_image_caption',
        'featured_image' => '\\PublishPress\\Checklists\\Core\\Requirement\\Featured_image',
        'filled_excerpt' => '\\PublishPress\\Checklists\\Core\\Requirement\\Filled_excerpt',
        'image_alt_count' => '\\PublishPress\\Checklists\\Core\\Requirement\\Image_alt_count',
        'image_alt' => '\\PublishPress\\Checklists\\Core\\Requirement\\Image_alt',
        'internal_links' => '\\PublishPress\\Checklists\\Core\\Requirement\\Internal_links',
        'prohibited_categories' => '\\PublishPress\\Checklists\\Core\\Requirement\\Prohibited_categories',
        'prohibited_tags' => '\\PublishPress\\Checklists\\Core\\Requirement\\Prohibited_tags',
        'required_categories' => '\\PublishPress\\Checklists\\Core\\Requirement\\Required_categories',
        'required_tags' => '\\PublishPress\\Checklists\\Core\\Requirement\\Required_tags',
        'tags_count' => '\\PublishPress\\Checklists\\Core\\Requirement\\Tags_count',
        'title_count' => '\\PublishPress\\Checklists\\Core\\Requirement\\Title_count',
        'validate_links' => '\\PublishPress\\Checklists\\Core\\Requirement\\Validate_links',
        'words_count' => '\\PublishPress\\Checklists\\Core\\Requirement\\Words_count',
        
        // Pro requirements
        'heading_in_hierarchy' => '\\PublishPress\\ChecklistsPro\\Accessibility\\Requirement\\HeadingInHierarchy',
        'single_h1_per_page' => '\\PublishPress\\ChecklistsPro\\Accessibility\\Requirement\\SingleH1PerPage',
        'table_header' => '\\PublishPress\\ChecklistsPro\\Accessibility\\Requirement\\TableHeader',
        'all_in_one_seo_headline_score' => '\\PublishPress\\ChecklistsPro\\AllInOneSeo\\Requirement\\AIOHeadlineScore',
        'all_in_one_seo_score' => '\\PublishPress\\ChecklistsPro\\AllInOneSeo\\Requirement\\AIOSeoScore',
        'approved_by_user' => '\\PublishPress\\ChecklistsPro\\ApprovedByUser\\Requirement\\ApprovedByUser',
        'featured_image_height' => '\\PublishPress\\ChecklistsPro\\FeaturedImageSize\\Requirement\\FeaturedImageHeight',
        'featured_image_width' => '\\PublishPress\\ChecklistsPro\\FeaturedImageSize\\Requirement\\FeaturedImageWidth',
        'image_count' => '\\PublishPress\\ChecklistsPro\\ImageCount\\Requirement\\ImageCount',
        'audio_count' => '\\PublishPress\\ChecklistsPro\\AudioCount\\Requirement\\AudioCount',
        'video_count' => '\\PublishPress\\ChecklistsPro\\VideoCount\\Requirement\\VideoCount',
        'no_heading_tags' => '\\PublishPress\\ChecklistsPro\\NoHeadingTags\\Requirement\\NoHeadingTags',
        'publish_time_exact' => '\\PublishPress\\ChecklistsPro\\PublishTime\\Requirement\\PublishTimeExact',
        'publish_time_future' => '\\PublishPress\\ChecklistsPro\\PublishTime\\Requirement\\PublishTimeFuture',
        'rank_math_score' => '\\PublishPress\\ChecklistsPro\\RankMath\\Requirement\\RankMathScore',
    ];

    /**
     * Map of requirement names to their groups
     */
    const GROUP_MAPPING = [
        'approved_by' => 'approval',
        'categories_count' => 'categories',
        'external_links' => 'links',
        'featured_image_alt' => 'featured_image',
        'featured_image_caption' => 'featured_image',
        'featured_image' => 'featured_image',
        'filled_excerpt' => 'content',
        'image_alt_count' => 'images',
        'image_alt' => 'images',
        'internal_links' => 'links',
        'prohibited_categories' => 'categories',
        'prohibited_tags' => 'tags',
        'required_categories' => 'categories',
        'required_tags' => 'tags',
        'tags_count' => 'tags',
        'title_count' => 'title',
        'validate_links' => 'links',
        'words_count' => 'content',
        
        // Pro requirements
        'heading_in_hierarchy' => 'accessibility',
        'single_h1_per_page' => 'accessibility',
        'table_header' => 'accessibility',
        'all_in_one_seo_headline_score' => 'all_in_one_seo',
        'all_in_one_seo_score' => 'all_in_one_seo',
        'approved_by_user' => 'approval',
        'featured_image_height' => 'featured_image',
        'featured_image_width' => 'featured_image',
        'image_count' => 'images',
        'audio_count' => 'audio_video',
        'video_count' => 'audio_video',
        'no_heading_tags' => 'content',
        'publish_time_exact' => 'publish_date_time',
        'publish_time_future' => 'publish_date_time',
        'rank_math_score' => 'rank_math',
    ];

    /**
     * Map of requirement names to their positions
     */
    const POSITION_MAPPING = [
        'approved_by' => 170,
        'categories_count' => 30,
        'external_links' => 110,
        'featured_image_alt' => 105,
        'featured_image_caption' => 106,
        'featured_image' => 102,
        'filled_excerpt' => 90,
        'image_alt_count' => 135,
        'image_alt' => 130,
        'internal_links' => 100,
        'prohibited_categories' => 50,
        'prohibited_tags' => 80,
        'required_categories' => 40,
        'required_tags' => 70,
        'tags_count' => 60,
        'title_count' => 10,
        'validate_links' => 120,
        'words_count' => 20,
        
        // Pro requirements
        'heading_in_hierarchy' => 142,
        'single_h1_per_page' => 143,
        'table_header' => 140,
        'all_in_one_seo_headline_score' => 150,
        'all_in_one_seo_score' => 149,
        'approved_by_user' => 171,
        'featured_image_height' => 104,
        'featured_image_width' => 103,
        'image_count' => 101,
        'audio_count' => 109,
        'video_count' => 111,
        'no_heading_tags' => 108,
        'publish_time_exact' => 161,
        'publish_time_future' => 107,
        'rank_math_score' => 141,
    ];

    /**
     * List of setting suffixes to copy when duplicating
     */
    const SETTING_SUFFIXES = [
        '_rule',
        '_min',
        '_max',
        '_multiple',
        '_can_ignore',
        '_editable_by',
        '_title',
        '_users',
        '_roles',
        '_field_key',
        '_taxonomy',
        '_terms'
    ];

    /**
     * Get title for a requirement
     */
    public static function getTitle($requirement_name)
    {
        // Check static mapping first
        if (isset(self::TITLE_MAPPING[$requirement_name])) {
            return self::TITLE_MAPPING[$requirement_name];
        }
        
        // For dynamic taxonomy requirements, extract taxonomy name and format it
        if (preg_match('/(.+)_count$/', $requirement_name, $matches) && 
            $requirement_name !== 'categories_count' && 
            $requirement_name !== 'tags_count' &&
            $requirement_name !== 'words_count' &&
            $requirement_name !== 'title_count' &&
            $requirement_name !== 'image_alt_count' &&
            $requirement_name !== 'image_count' &&
            $requirement_name !== 'audio_count' &&
            $requirement_name !== 'video_count') {
            $taxonomy_name = $matches[1];
            return ucwords(str_replace('_', ' ', $taxonomy_name)) . ' Count';
        }
        
        return ucwords(str_replace('_', ' ', $requirement_name));
    }

    /**
     * Get class name for a requirement
     */
    public static function getClassName($requirement_name)
    {
        // Check static mapping first
        if (isset(self::CLASS_MAPPING[$requirement_name])) {
            return self::CLASS_MAPPING[$requirement_name];
        }
        
        // Check if this is a dynamic taxonomy requirement
        // Pattern: {taxonomy_name}_count (but not categories_count or tags_count)
        if (preg_match('/_count$/', $requirement_name) && 
            $requirement_name !== 'categories_count' && 
            $requirement_name !== 'tags_count' &&
            $requirement_name !== 'words_count' &&
            $requirement_name !== 'title_count' &&
            $requirement_name !== 'image_alt_count' &&
            $requirement_name !== 'image_count' &&
            $requirement_name !== 'audio_count' &&
            $requirement_name !== 'video_count') {
            // This is likely a custom taxonomy count requirement
            return '\\PublishPress\\Checklists\\Core\\Requirement\\Taxonomies_count';
        }
        
        return false;
    }

    /**
     * Get group for a requirement
     */
    public static function getGroup($requirement_name)
    {
        // Check static mapping first
        if (isset(self::GROUP_MAPPING[$requirement_name])) {
            return self::GROUP_MAPPING[$requirement_name];
        }
        
        // Check if this is a dynamic taxonomy requirement
        if (preg_match('/_count$/', $requirement_name) && 
            $requirement_name !== 'categories_count' && 
            $requirement_name !== 'tags_count' &&
            $requirement_name !== 'words_count' &&
            $requirement_name !== 'title_count' &&
            $requirement_name !== 'image_alt_count' &&
            $requirement_name !== 'image_count' &&
            $requirement_name !== 'audio_count' &&
            $requirement_name !== 'video_count') {
            return 'taxonomies';
        }
        
        return 'general';
    }

    /**
     * Get position for a requirement
     */
    public static function getPosition($requirement_name)
    {
        // Check static mapping first
        if (isset(self::POSITION_MAPPING[$requirement_name])) {
            return self::POSITION_MAPPING[$requirement_name];
        }
        
        // Dynamic taxonomy requirements get position 200+
        if (preg_match('/_count$/', $requirement_name) && 
            $requirement_name !== 'categories_count' && 
            $requirement_name !== 'tags_count' &&
            $requirement_name !== 'words_count' &&
            $requirement_name !== 'title_count' &&
            $requirement_name !== 'image_alt_count' &&
            $requirement_name !== 'image_count' &&
            $requirement_name !== 'audio_count' &&
            $requirement_name !== 'video_count') {
            return 200;
        }
        
        return 1000;
    }
}
