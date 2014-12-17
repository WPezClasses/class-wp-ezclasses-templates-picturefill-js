<?php
/** 
 * An ez-Tized fork + makeover of ChrisB's WordPress plugin RespImage. A plugin that made / makes Scott Jehl's picturefill.js WP-friendly. 
 *
 * TODO   
 *
 * PHP version 5.3
 *
 * LICENSE: TODO
 *
 * @package WPezClasses
 * @author Mark Simchock <mark.simchock@alchemyunited.com>
 * @since 0.5.0
 * @license TODO
 */
 
/**
 * == Change Log ==
 * 
 * == 13 Nov 2014 ==
 * --- FIXED: Cleaned up some naming and such
 * --- ADDED: Method options_scrset_image_sizes_exclude() and associated functionality
 *
 * == 9 October 2014 ==
 * --- Ready!
 */
 
/**
 * == TODO ==
 *
 */
 
/**
 * == References == 
 * 
 * - ChrisB: http://elf02.de/2014/07/14/respimage-wordpress-plugin/
 *
 * - Scott Jehl: http://scottjehl.github.io/picturefill/
 *
 * - http://scottjehl.github.io/picturefill/
 *
 * - http://alistapart.com/article/responsive-images-in-practice
 *
 * - https://html.spec.whatwg.org/multipage/embedded-content.html
 * 
 * - http://ericportis.com/posts/2014/srcset-sizes/
 */
 

// No WP? Die! Now!!
if ( ! defined('ABSPATH')) {
	header( 'HTTP/1.0 403 Forbidden' );
    die();
}

if (! class_exists('Class_WP_ezClasses_Templates_Picturefill_js') ) {
  class Class_WP_ezClasses_Templates_Picturefill_js extends Class_WP_ezClasses_Master_Singleton {
  
    private $_version;
	private $_url;
	private	$_path;
	private $_path_parent;
	private $_basename;
	private $_file;
		
	protected $_obj_wp_enqueue;
  
    protected $_arr_init;
	protected $_bool_remove_width_height;
	
	protected function __construct() {
	  parent::__construct();
	}
	
	/**
	 *
	 */
	public function ez__construct($arr_args = ''){

	  $this->setup();
	
	  $this->_arr_init = WPezHelpers::ez_array_merge(array($this->init_defaults(), $arr_args));
	  
	  $this->_bool_remove_width_height = (bool)$this->_arr_init['remove_width_height_filter'];
	  
	  if ( $this->_arr_init['native'] !== true ){
	    $this->_obj_wp_enqueue = Class_WP_ezClasses_ezCore_WP_Enqueue::ez_new();
	    add_action( 'wp_enqueue_scripts', array($this,'wp_enqueue_picturefill_js') );
	  }

	  if ( $this->_arr_init['remove_width_height_filter'] === true ){	  
	    add_filter( 'post_thumbnail_html', array($this, 'filter_remove_width_height_attributes'), 10 );
	    add_filter( 'image_send_to_editor', array($this, 'filter_remove_width_height_attributes'), 10 );
	  }

	  add_filter('image_send_to_editor', array($this, 'insert_data_attribute_with_id'), 10, 9);
	  add_filter('the_content', array($this, 'filter_picturefill_images'), 99);
	}
	
	
	/**
	 * basic defaults
	 */
    public function init_defaults(){
	
	  $arr_defaults = array(
	  
	    'remove_width_height_filter'	=> false,					// when inserting media into the_content(), remmove width= and height=
	    'fallback'						=> true,					// use a fallback img?
		'fallback_size'					=> 'full',					// which image size should be used for the fallback
		'native'						=> false,					// if you want to load picturefill.js yourself then set this to true.
		'async'							=> true,					// note: not being used atm. included for completeness
		'sizes'							=> 'a',						// this value should be a valid key in the array in options_sizes()
		'data_attribute'				=> trim('picturefill'),		// once you starting using this class DO NOT change this value. it'll muck up any previous usage.
		'img_add_class'					=> ''						// for example, for Bootstrap you might want 'img-responsive' ref: http://getbootstrap.com/css/#images
        );
		
	  return $arr_defaults;
	}
	
	protected function setup(){
	
	  $this->_version = '0.5.0';
	  $this->_url = plugin_dir_url( __FILE__ );
	  $this->_path = plugin_dir_path( __FILE__ );
	  $this->_path_parent = dirname($this->_path);
	  $this->_basename = plugin_basename( __FILE__ );
	  $this->_file = __FILE__ ;
	
	}
	
	/**
	 * Where the image options magic happens. chances are very good you're gotta rework this method once you extend this class
	 * 
	 * Getting your current image sizes (see examples): http://codex.wordpress.org/Function_Reference/get_intermediate_image_sizes
	 */
	public function options_images(){
	
	  $arr_options_images = array(
	  
	    'thumb'		=> array(
		  'active'		=> true,				// simple bool flag. when false this entry will be ignored
		  'name'		=> 'thumbnail',			// name used for add_image_size()
		  'w'			=> 'w',					// w = width. value 'w' defaults to the image's width setting (in WP), else specify your own integer for this image name. 
		  ),
		  
	    'med'		=> array(
		  'active'		=> true,
		  'name'		=> 'medium',
		  'w'			=> 'w'
		  ),	

	    'lrg'		=> array(
		  'active'		=> true,
		  'name'		=> 'large',
		  'w'			=> 'w'
		  ),		  
	  );
	  
	  return $arr_options_images;
	}
	
	/**
	 * Again, probably something you'll reconfig in your class
	 *
	 * IMPORTANT - You must have at least one size. The default will be the first in the array. If there are no sizes (below) we quit / return
	 */
	public function options_sizes(){
	
	  $arr_options_sizes = array(

	    'a'		=> '(min-width: 1200px) 100vw, (min-width: 992px) 100vw, (min-width: 768px) 100vw, (min-width: 480px) 100vw, 100vw)'
	  );
	  
	  return $arr_options_sizes;
	}
	
	/**
	 * Again, probably something you'll reconfig in your class
	 *
	 * For a given options_sizes() key, which WP image sizes should be EXCLUDED from the srcset='...' list
	 *
	 * NOTE: The default is to use all WP sizes. If there is no key or the array for the key is empty or not an array then we go to the default (i.e., no exclude, use'em all).
	 */
	public function options_scrset_image_sizes_exclude(){
	  
	  $image_sizes_exclude = array(
	  
	    'a'		=> array(),
	  );
	  
	  return $image_sizes_exclude;
	}
	
	
	public function wp_enqueue_picturefill_js(){
	
	  $arr_args['arr_args'] = $this->picturefill_js_enqueue();
	
      // register'em
      $this->_obj_wp_enqueue->ez_rs($arr_args);
	  
	  // do'em! enqueue'em!!
	  $this->_obj_wp_enqueue->wp_enqueue_do($this->picturefill_js_enqueue());
	}
	
	
	protected function picturefill_js_enqueue(){
		
	  $arr_scripts_and_styles = array(
	    'picturefill_js_min'			=> array(	
		  'active'						=> true,
		  'host'						=> 'WPezClasses',
		  'note'						=> "Scott Jehl: http://scottjehl.github.io/picturefill/",
		  'conditional_tags'			=> array(),
		  'type'						=> 'script',
		  'handle'						=> 'picturefilljs_min',
		  'src'							=> $this->_url . 'js/picturefill.min.js',
		  'deps'						=> false,
		  'ver'							=> '2.1.0',
		  //	'media'					=> NULL,	
		  'in_footer'					=> false,   // picturefill says to put it in the head. footer is too late
		),					
	  );
	  
	  return $arr_scripts_and_styles;
	}
	
	/**
	 * If you want to remove the width and height then you need to set _remove_width_height to true
	 */
    public function set_remove_width_height( $bool_flag = false ){
	  
	  $this->_bool_remove_width_height = false;
	  if ( $bool_flag === true ){
		$this->_bool_remove_width_height = true;
	  }	  
	}

	/**
	 *
	 */
	public function filter_remove_width_height_attributes( $str_html = '' ) {
	  
	  if ( $this->_bool_remove_width_height === true ){
   	    $str_html = preg_replace( '/(width|height)="\d*"\s/', "", $str_html );
	  }
	  return $str_html;
	}
	
    /**
     * Add data-responsive attribute
     */
    public function insert_data_attribute_with_id($html, $id, $caption, $title, $align, $url) {
	
	  $str_data_attribute = $this->_arr_init['data_attribute'];
	  $html = str_replace('<img', '<img data-' . $str_data_attribute . '="' . $id . '"', $html);
	  
	  return $html;
    }
	
    /**
     * Filter all images with a data-responsive attribute
     */
    public function filter_picturefill_images($content) {
	
        // Check for feed
        if( is_feed() ) {
            return $content;
        }

        $content = preg_replace_callback(
            '/<img.*?'. $this->_arr_init['data_attribute'] . '=[\'"](.*?)[\'"].*?>/i',
            array($this, 'insert_picturefill_args'),
            $content
        );
        return $content;
    }


    /**
     * Replace images with srcset image markup
     */
    public function insert_picturefill_args($arr_0_img_markup_1_id) {
	  
	  $img_markup = $arr_0_img_markup_1_id[0];
      $image_id = $arr_0_img_markup_1_id[1];

      // Check for embedded mq id
      if( strpos($image_id, '/') ) {
        $a = explode('/', $image_id);
        if(count($a) === 2) {
          list($image_id, $str_mq_key) = $a;
        }
      }
	  
	  // Check for existing image id
      $imgsrc_full = wp_get_attachment_image_src($image_id, $this->_arr_init['fallback_size']);
      if( $imgsrc_full === false ) {
        return $img_markup;
      }

	  $image_id = intval($image_id);
	  
	  // sort out the 
	  $arr_sizes = $this->options_sizes();
	  //	  $str_mq = $arr_mq[$this->_arr_init['sizes']];
	  $str_sizes_key = $this->_arr_init['sizes'];
	  
	  // when using this method directly you can pass in a third arg to specify the sizes[] you want for that particular img
	  if ( isset($arr_0_img_markup_1_id[2]) && isset($arr_sizes[$arr_0_img_markup_1_id[2]]) ){ 
	    $str_sizes_key = $arr_0_img_markup_1_id[2]; 
	  } elseif ( ! isset($arr_sizes[$str_sizes_key]) ){ 
	    // if all else fails, we'll look the first key in the array
		reset($arr_sizes);
	    $str_sizes_key = key($arr_sizes);
		// if the sizes() is empty then return. no size means we can not continue
		if ( empty($str_sizes_key)){
		  return;
		} 		
	  }
	  // we're good!
	  $str_sizes = $arr_sizes[$str_sizes_key];

	  /**
	   * when using this method directly you can pass in a fourth  arg to specify the class you want to add to the image <img> tag
	   * for example, for Bootstrap you might want 'img-responsive' ref: http://getbootstrap.com/css/#images
	   */
	  $str_img_add_class = '';
	  if ( isset($arr_0_img_markup_1_id[3]) ){ 
	    $str_img_add_class = sanitize_text_field($arr_0_img_markup_1_id[3]);
	  } elseif ( isset($this->_arr_init['img_add_class']) ){ 
	    $str_img_add_class = sanitize_text_field($this->_arr_init['img_add_class']);
	  } 
		
        // Check image and mq id
        if( empty($image_id) || empty($str_sizes)) {
            return $img_markup;
        }

        // Get class names
        preg_match('/class=[\'"](.*?)[\'"]/i', $img_markup, $arr_class_match);
        $class_names = '';
		if ( ! empty($arr_class_match[1]) ) {
		  $class_names = ' class="' . trim($arr_class_match[1]. ' ' . $str_img_add_class) . '" ';
		}

        // Check for fallback image
        $str_img_fallback = '';
		if ( $this->_arr_init['fallback'] === true ){
		  $str_img_fallback = ' src="' . $imgsrc_full[0] . '"';
        }
		
		// returns all the registered images and their settings (width, height, crop)
		$arr_get_image_sizes = WPezHelpers::ez_get_image_sizes();
		
		// let get the size names we'll be exclusing for this sizes[] key.
		$arr_options_scrset_image_sizes_exclude = $this->options_scrset_image_sizes_exclude();
		
		$arr_scrset_image_exclude = array();
		if ( isset($arr_options_scrset_image_sizes_exclude[$str_sizes_key]) && is_array($arr_options_scrset_image_sizes_exclude[$str_sizes_key]) ){
		 $arr_scrset_image_exclude = $arr_options_scrset_image_sizes_exclude[$str_sizes_key];
		}

        // Get the images of the whole that we're using for responsive purposes
        foreach($this->options_images() as $key => $arr_value) {
		
		  // some quick "validation" before we go on
		  if ( isset($arr_value['active']) && isset($arr_value['name']) && isset($arr_get_image_sizes[$arr_value['name']]) && $arr_value['active'] === true ) {
		    
			// if the name is in the exclude array then skip it (i.e., continue) so it's not in the srcset
		    if ( in_array($arr_value['name'], $arr_scrset_image_exclude) ) {
			  continue;
			}
		    $mix_img_src = wp_get_attachment_image_src($image_id, $arr_value['name']);
            if( $mix_img_src === false ) {
              continue;
            }
			
			$int_width = $arr_get_image_sizes[$arr_value['name']]['width'];
			// default to the width?
			if ( isset($arr_value['w']) && strtolower($arr_value['w']) != 'w' ){
			  $int_width = intval($arr_value['w']);
			}
		    $srcset[] = $mix_img_src[0] . ' ' . trim($int_width) . 'w, ';
		  
		  }
        }

        // Check for valid sizes
        if( ! isset($srcset)) {
            return $img_markup;
        }
		
		$markup = '<img ' . $class_names . ' ' . $str_img_fallback . ' srcset="' . trim(implode($srcset), ', ') . '" sizes="' . $str_sizes . '">';
     return $markup;
    }

  }	
}