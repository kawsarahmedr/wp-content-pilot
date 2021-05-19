<?php
// don't call the file directly
defined( 'ABSPATH' ) || exit();

class WPCP_Article extends WPCP_Module {
    
    /**
     * @var string
     */
    protected $module = 'article';
    
    /**
     * The single instance of the class
     *
     * @var $this ;
     */
    protected static $_instance = null;
    
    /**
     * WPCP_Module constructor.
     */
    public function __construct() {
        //option fields
        add_action( 'wpcp_article_campaign_options_meta_fields', 'wpcp_keyword_suggestion_field' );
        add_action( 'wpcp_article_campaign_options_meta_fields', 'wpcp_keyword_field' );
        parent::__construct( $this->module );
    }
    
    /**
     * @return string
     */
    public function get_module_icon() {
        return '';
    }
    
    /**
     * @return array
     * @since 1.2.0
     */
    public function get_template_tags() {
        return array(
            'title'      => __( 'Title', 'wp-content-pilot' ),
            'excerpt'    => __( 'Summary', 'wp-content-pilot' ),
            'content'    => __( 'Content', 'wp-content-pilot' ),
            'image_url'  => __( 'Main image url', 'wp-content-pilot' ),
            'source_url' => __( 'Source link', 'wp-content-pilot' ),
        );
    }
    
    /**
     * @return string
     * @since 1.2.0
     */
    public function get_default_template() {
        $template
            = <<<EOT
<img src="{image_url}">
{content}
<br> <a href="{source_url}" target="_blank">Source</a>
EOT;
        
        return $template;
    }
    
    /**
     * @param $post
     */
    public function add_campaign_option_fields( $post ) {
        
        echo WPCP_HTML::start_double_columns();
        echo WPCP_HTML::text_input( array(
        	'name' => '_article_origin',
	        'label' => __('Put the country codes for specific search of article','wp-content-pilot'),
	        'default' => 'us',
	        'wrapper_class' => 'pro',
	        'attrs'         => array(
                'disabled' => 'disabled',
            ),
	        'desc' => sprintf(__('Check this link to get the <a href="%s" target="_blank">Country Codes</a>.','wp-content-pilot'), 'https://developers.google.com/custom-search/docs/xml_results_appendices#countryCodes'),
	        
        ));
//	    echo WPCP_HTML::text_input( array(
//		    'name' => '_article_restrict',
//		    'label' => __('Restrict search results for specific regions','wp-content-pilot'),
//		    'wrapper_class' => 'pro',
//		    'attrs'         => array(
//			    'disabled' => 'disabled',
//		    ),
//		    'desc' => sprintf(__('Check this link to get the <a href="%s" target="_blank">Restricted Country Codes</a>.','wp-content-pilot'), 'https://developers.google.com/custom-search/docs/xml_results_appendices#countryCollections'),
//
//	    ));
     
//        echo WPCP_HTML::select_input( array(
//            'name'          => '_article_region',
//            'label'         => __( 'Select region to search article', 'wp-content-pilot' ),
//            'options'       => $this->get_article_region(),
//            'default'       => 'global',
//            'class'         => 'wpcp-select2',
//            'wrapper_class' => 'pro',
//            'attrs'         => array(
//                'disabled' => 'disabled',
//            )
//        ) );
//		echo WPCP_HTML::select_input( array(
//			'name'          => '_article_language',
//			'label'         => __( 'Select language to search article', 'wp-content-pilot' ),
//			'options'       => $this->get_article_language(),
//			'default'       => 'en',
//			'wrapper_class' => 'pro',
//			'attrs'         => array(
//				'disabled' => 'disabled',
//			)
//		) );
        echo WPCP_HTML::end_double_columns();
        
    }
    
    /**
     * @param $campaign_id
     * @param $posted
     */
    public function save_campaign_meta( $campaign_id, $posted ) {
    
    }
    
    /**
     * @param $sections
     *
     * @return array
     * @since 1.2.0
     */
    public function get_setting_section( $sections ) {
        $sections[] = [
            'id'    => 'wpcp_settings_article',
            'title' => __( 'Article Settings', 'wp-content-pilot' )
        ];
        
        return $sections;
    }
    
    /**
     * @param $fields
     *
     * @return array
     * @since 1.2.0
     */
    public function get_setting_fields( $fields ) {
        $fields['wpcp_settings_article'] = [
            array(
                'name'        => 'banned_hosts',
                'label'       => __( 'Banned Hosts', 'wp-content-pilot' ),
                'desc'        => __( 'Articles from the above hosts will be rejected. put single url/host per line.', 'wp-content-pilot' ),
                'placeholder' => __( "example.com \n example1.com", 'wp-content-pilot' ),
                'type'        => 'textarea',
            ),
            array(
                'name'              => 'api_key',
                'label'             => __( 'Google Custom Search API key', 'wp-content-pilot-pro' ),
                'desc'              => sprintf( __( 'Learn to create your Google Custom Search API API key <a href="%s" target="_blank">here</a>.', 'wp-content-pilot' ), 'https://pluginever.com/docs/wp-content-pilot/how-to-create-google-custom-search-api/' ),
                'type'              => 'password',
                'default'           => '',
                'sanitize_callback' => 'esc_html',
            )
        ];
        
        return $fields;
    }
    
    
    /**
     * @param int $campaign_id
     *
     * @return array|mixed|WP_Error
     * @throws ErrorException
     * @since 1.2.0
     */
    public function get_post( $campaign_id ) {
        //before it was getting keywords but now we are changing to source instead of keywords
        //it can be anything
        $keywords = $this->get_campaign_meta( $campaign_id );
        if ( empty( $keywords ) ) {
            return new WP_Error( 'missing-data', __( 'Campaign do not have keyword to proceed, please set keyword', 'wp-content-pilot' ) );
        }
        
        $api_key = wpcp_get_settings( 'api_key', 'wpcp_settings_article' );
        wpcp_logger()->info( __( 'Checking google custom search api key for authentication', 'wp-content-pilot' ), $campaign_id );
        if ( empty( $api_key ) ) {
            wpcp_disable_campaign( $campaign_id );
            
            $notice = __( 'Google custom search api key is not set.So, the campaign wont run, disabling campaign.', 'wp-content-pilot' );
            wpcp_logger()->error( $notice );
            
            return new WP_Error( 'missing-data', $notice );
        }
        
        wpcp_logger()->info( __( 'Loaded Article campaign', 'wp-content-pilot' ), $campaign_id );
        
        //loop through keywords
        foreach ( $keywords as $keyword ) {
            wpcp_logger()->info( sprintf( __( 'Looking for article for the keyword [ %s ]', 'wp-content-pilot' ), $keyword ), $campaign_id );
            
            if ( $this->is_deactivated_key( $campaign_id, $keyword ) ) {
//				$reactivate_keyword_action = add_query_arg( [
//					'campaign_id' => $campaign_id,
//					'keyword'     => $keyword,
//					'action'      => 'wpcp_reactivate_keyword'
//				], admin_url( 'admin-post.php' ) );
//				wpcp_logger()->info( sprintf( __( 'The keyword is deactivated for 1 hr because last time could not find any article with keyword [%s] %s reactivate keyword %s', 'wp-content-pilot' ), $keyword, '<a href="' . $reactivate_keyword_action . '">', '</a>' ), $campaign_id );
                wpcp_logger()->info( __( 'The keyword is deactivated for 1 hr because last time could not find any article with keyword [%s]', 'wp-content-pilot' ), $campaign_id );
                continue;
            }
            
            //get links from database
            wpcp_logger()->info( __( 'Checking for cached links in store', 'wp-content-pilot' ), $campaign_id );
            $links = $this->get_links( $keyword, $campaign_id );
            if ( empty( $links ) ) {
                wpcp_logger()->info( __( 'No cached links in store. Generating new links...', 'wp-content-pilot' ), $campaign_id );
                $this->discover_links( $campaign_id, $keyword );
                $links = $this->get_links( $keyword, $campaign_id );
            }
            
            wpcp_logger()->info( __( 'Looping through cached links for publishing article', 'wp-content-pilot' ), $campaign_id );
            foreach ( $links as $link ) {
                wpcp_logger()->info( sprintf( __( 'Generating article from [%s]', 'wp-content-pilot' ), $link->url ), $campaign_id );
                
                $this->update_link( $link->id, [ 'status' => 'failed' ] );
                
                $curl = $this->setup_curl();
                $curl->get( $link->url );
                
                if ( $curl->isError() && $this->initiator != 'cron' ) {
                    wpcp_logger()->error( sprintf( __( "Failed processing link reason [%s]", 'wp-content-pilot' ), $curl->getErrorMessage() ), $campaign_id );
                    continue;
                }
                
                wpcp_logger()->info( __( "Extracting post content from request", 'wp-content-pilot' ), $campaign_id );
                
                $html        = $curl->response;
                $readability = new WPCP_Readability();
                $readable    = $readability->parse( $html, $link->url );
                if ( is_wp_error( $readable ) ) {
                    wpcp_logger()->error( sprintf( __( "Failed readability reason [%s] changing to different link", 'wp-content-pilot' ), $readable->get_error_message() ), $campaign_id );
                    continue;
                }
                
                //check if the clean title metabox is checked and perform title cleaning
                $check_clean_title = wpcp_get_post_meta( $campaign_id, '_clean_title', 'off' );
                
                if ( 'on' == $check_clean_title ) {
                    wpcp_logger()->info( __( 'Cleaning title', 'wp-content-pilot' ), $campaign_id );
                    $title = wpcp_clean_title( $readability->get_title() );
                } else {
                    $title = html_entity_decode( $readability->get_title(), ENT_QUOTES );
                }
                
                wpcp_logger()->info( __( 'Making article content from response', 'wp-content-pilot' ), $campaign_id );
                $article = array(
                    'title'      => $title,
                    'author'     => $readability->get_author(),
                    'image_url'  => $readability->get_image(),
                    'excerpt'    => $readability->get_excerpt(),
                    'language'   => $readability->get_language(),
                    'content'    => $readability->get_content(),
                    'source_url' => $link->url,
                );
                
                wpcp_logger()->info( __( 'Article processed from campaign', 'wp-content-pilot' ), $campaign_id );
                $this->update_link( $link->id, [ 'status' => 'success', 'meta' => '' ] );
                
                return $article;
            }
        }
        
        $log_url = admin_url( '/edit.php?post_type=wp_content_pilot&page=wpcp-logs' );
        
        return new WP_Error( 'campaign-error', __( sprintf( 'No article generated check <a href="%s">log</a> for details.', $log_url ), 'wp-content-pilot' ) );
    }
    
    
    /**
     * @param $campaign_id
     * @param $keyword
     *
     * @return bool|mixed|WP_Error
     * @throws ErrorException
     * @since 1.2.0
     */
    protected function discover_links( $campaign_id, $keyword ) {
        $page_key         = $this->get_unique_key( $keyword );
        $page_number      = wpcp_get_post_meta( $campaign_id, $page_key, 0 );
        $api_key          = wpcp_get_settings( 'api_key', 'wpcp_settings_article' );
        $google_custom_id = '359394892d6b9fe2c';
        $host             = 'google.com';
        $url              = "https://www.googleapis.com/customsearch/v1";
        
        $args = apply_filters( 'wpcp_article_search_args' ,
        	array(
		        'key'        => urlencode( trim( $api_key ) ),
		        'cx'         => urlencode( trim( $google_custom_id ) ),
		        'q'          => urlencode( trim( $keyword ) ),
		        'gl'          => 'us',
		        'googlehost' => urlencode( $host ),
		        'start'      => ( $page_number * 10 ),
	        ),
            $campaign_id
        );
	    $endpoint = add_query_arg( array( $args ), $url );
        
        //wpcp_logger()->debug( sprintf( 'Searching page url [%s]', $endpoint ), $campaign_id );
        wpcp_logger()->info( sprintf( __( 'Searching page url [%s]', 'wp-content-pilot' ), $endpoint ), $campaign_id );
        
        $curl     = $this->setup_curl();
        $response = $curl->get( $endpoint );
        
        if ( $curl->isError() ) {
            wpcp_logger()->error( $curl->errorMessage, $campaign_id );
            $this->deactivate_key( $campaign_id, $keyword );
            
            return $response;
        }

//		if ( ! $response instanceof \SimpleXMLElement ) {
//			$response = simplexml_load_string( $response );
//		}
        
        wpcp_logger()->info( __( 'Extracting response from request', 'wp-content-pilot' ), $campaign_id );
        $response = json_encode( $response );
        $response = json_decode( $response, true );
        
        //check if links exist
        if ( empty( $response ) || ! isset( $response['items'] ) || empty( $response['items'] ) ) {
            $message = __( 'Could not find any links from search engine, deactivating keyword for an hour.', 'wp-content-pilot' );
            wpcp_logger()->error( $message, $campaign_id );
            $this->deactivate_key( $campaign_id, $keyword );
            
            return new WP_Error( 'no-links-found', $message );
        }
        
        $items = $response['items'];
        
        wpcp_logger()->info( __( 'Getting banned hosts for skipping links', 'wp-content-pilot' ), $campaign_id );
        $banned_hosts = wpcp_get_settings( 'banned_hosts', 'wpcp_settings_article' );
        $banned_hosts = preg_split( '/\n/', $banned_hosts );
        $banned_hosts = array_merge( $banned_hosts, array(
            'youtube.com',
            'wikipedia',
            'dictionary',
            'youtube',
            'wikihow',
            'yahoo',
            'bing',
        ) );
        
        $links = [];
        
        wpcp_logger()->info( __( 'Finding links from response and inserting into database', 'wp-content-pilot' ), $campaign_id );
        foreach ( $items as $item ) {
            $item_link = $item['link'];
            
            $item_link = explode( '&r=', $item_link );
            if ( count( $item_link ) == 1 ) {
                $item_link = explode( '&url=', $item['link'] );
            }
            $link = count( $item_link ) > 1 ? $item_link[1] : $item_link[0];
            $link = urldecode( $link );
            
            foreach ( $banned_hosts as $banned_host ) {
                if ( stristr( $link, $banned_host ) ) {
	                continue;
                }
            }
            
            if ( stristr( $link,'tripadvisor' ) || stristr( $link, 'bing' ) ) {
            	continue;
            }
	        
	        if ( stristr( $link, 'wikipedia' ) ) {
                continue;
            }
            
            if ( wpcp_is_duplicate_url( $link ) ) {
                continue;
            }
            
            $skip = apply_filters( 'wpcp_skip_duplicate_title', false, $item['title'], $campaign_id );
            if ( $skip ) {
                continue;
            }
            
            $links[] = [
                'url'     => esc_url( $link ),
                'title'   => $item['title'],
                'for'     => $keyword,
                'camp_id' => $campaign_id
            ];
        }
        
        $total_inserted = $this->inset_links( $links );
        
        wpcp_update_post_meta( $campaign_id, $page_key, $page_number + 1 );
        wpcp_logger()->info( sprintf( 'Total found links [%d] and accepted [%d] and rejected [%d]', count( $links ), $total_inserted, ( count( $links ) - $total_inserted ) ), $campaign_id );
        
        return true;
    }
    
    /**
     * Get all supported regions for searching article
     *
     * @return array
     * @since 1.1.1
     *
     */
    
    public function get_article_region() {
        $regions = array(
            'global' => 'Global Search',
            'es-AR'  => 'Spanish Argentina',
            'en-AU'  => 'English Australia',
            'de-AT'  => 'German Austria',
            'nl-BE'  => 'Dutch Belgium',
            'fr-BE'  => 'French Belgium',
            'pt-BR'  => 'Portuguese Brazil',
            'en-CA'  => 'English Canada',
            'fr-CA'  => 'French Canada',
            'es-CL'  => 'Spanish Chile',
            'da-DK'  => 'Danish Denmark',
            'fi-FI'  => 'Finnish Finland',
            'fr-FR'  => 'French France',
            'de-DE'  => 'German Germany',
            'zh-HK'  => 'Chinese Hong Kong',
            'en-IN'  => 'English India',
            'en-ID'  => 'English Indonesia',
            'it-IT'  => 'Italian Italy',
            'ja-JP'  => 'Japanese Japan',
            'ko-KR'  => 'Korean Korea',
            'en-MY'  => 'English Malaysia',
            'es-MX'  => 'Spanish Mexico',
            'nl-NL'  => 'Dutch Netherlands',
            'en-NZ'  => 'English New Zealand',
            'no-NO'  => 'Norwegian Norway',
            'zh-CN'  => 'Chinese China',
            'pl-PL'  => 'Polish Poland',
            'en-PH'  => 'English Philippines',
            'ru-RU'  => 'Russian Russia',
            'en-ZA'  => 'English South Africa',
            'es-ES'  => 'Spanish Spain',
            'sv-SE'  => 'Swedish Sweden',
            'fr-CH'  => 'French Switzerland',
            'de-CH'  => 'German Switzerland',
            'zh-TW'  => 'Chinese Taiwan',
            'tr-TR'  => 'Turkish Turkey',
            'en-GB'  => 'English United Kingdom',
            'en-US'  => 'English United States',
            'es-US'  => 'Spanish United States',
        
        
        );
        
        return $regions;
    }
    
    /**
     * Get all supported languages for searching article
     *
     * @return array
     * @since 1.1.1
     *
     */
    public function get_article_language() {
        
        $languages = array(
            'ar'      => 'Arabic',
            'eu'      => 'Basque',
            'bn'      => "Bengali",
            'bg'      => 'Bulgarian',
            'ca'      => 'Catalan',
            'zh-hans' => 'Simplified Chinese',
            'zh-hant' => 'Traditional Chinese',
            'hr'      => 'Croatian',
            'cs'      => 'Czech',
            'da'      => 'Danish',
            'nl'      => 'Dutch',
            'en'      => 'English',
            'en-gb'   => 'English - United Kingdom',
            'et'      => 'Estonian',
            'fi'      => 'Finish',
            'fr'      => 'French',
            'gl'      => 'Galician',
            'de'      => 'German',
            'gu'      => 'Gujrati',
            'he'      => 'Hebrew',
            'hi'      => 'Hindi',
            'hu'      => 'Hungarian',
            'is'      => 'Icelandic',
            'it'      => 'Italian',
            'jp'      => 'Japanese',
            'kn'      => 'Kannada',
            'ko'      => 'Korean',
            'lv'      => 'Latvian',
            'lt'      => 'Lithunian',
            'ms'      => 'Malay',
            'ml'      => 'Malayalam',
            'mr'      => 'Marathi',
            'nb'      => 'Norwegian',
            'pl'      => 'Polish',
            'pt-br'   => 'Portugese Brazil',
            'pt-pt'   => 'Portugese Portugal',
            'pa'      => 'Punjabi',
            'ro'      => 'Romanian',
            'ru'      => 'Russian',
            'sr'      => 'Serbian',
            'sk'      => 'Slovak',
            'sl'      => 'Slovenian',
            'es'      => 'Spanish',
            'sv'      => 'Swedish',
            'ta'      => 'Tamil',
            'te'      => 'Telegu',
            'th'      => 'Thai',
            'tr'      => 'Turkish',
            'uk'      => 'Ukrainian',
            'vi'      => 'Vietnamese',
        );
        
        return $languages;
    }
    
    
    /**
     * Main WPCP_Article Instance.
     *
     * Ensures only one instance of WPCP_Article is loaded or can be loaded.
     *
     * @return WPCP_Article Main instance
     * @since 1.0.0
     * @static
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
}

WPCP_Article::instance();
