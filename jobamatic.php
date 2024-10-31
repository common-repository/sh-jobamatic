<?php
/*
 * Plugin Name: Jobamatic
 * Plugin URI: http://www.ronferguson.net/wp-jobamatic
 * Description: Job listing page using the SimplyHired API.
 * Version: 1.0
 * Author: Ron Ferguson
 * Author URI: http://www.ronferguson.net
 * License: GPLv2
 */
/*
 * LICENSE
 *
 * Copyright (C) 2013  Ron Ferguson (r0nn1ef8580@gmail.com)
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */
if(!class_exists('Jobamatic')) {

  class Jobamatic {

    var $plugin_url;

    var $plugin_dir;

    var $db_opt = 'Jobamatic_Options';

    public function __construct() {
      $this->plugin_url = trailingslashit( WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)) );
      $this->plugin_dir = trailingslashit( plugin_dir_path(__FILE__) );

      // Include the libraries for the API.
      require_once($this->plugin_dir . '/includes/lib/SimplyHiredJobamaticAPI.php');

      if(is_admin()) {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'options_page'));
      } else {
        add_filter('the_content', array($this, 'content'));
      }
    }

    public function install() {
      $this->get_options();
    }

    public function deactivate() {

    }

    /**
     * Gets plugin config options
     *
     * @access public
     * @return array
     */
    public function get_options() {
      $options = array(
        'publisher_id' => '',
        'domain' => '',
        'query' => '(WordPress AND PHP) NOT Drupal',
        'per_page' => 10,
        'location' => '',
        'miles' => '',
        'sort' => 'rd',
        'post_id' => '',
        'advanced_search' => 1,
        'attribution' => 1
      );

      $saved = get_option($this->db_opt);

      if(!empty($saved)) {
        foreach ($saved as $key => $option) {
          $options[$key] = $option;
        }
      }

      if($saved != $options) {
        update_option($this->db_opt, $options);
      }

      return $options;
    }

    /**
     * Registers the options page.
     */
    function options_page() {
      add_options_page('Jobamatic Settings', 'Jobamatic', 'manage_options', 'jobamatic', array($this, 'handle_options'));
    }

    function register_settings() {
      register_setting('jobamatic_options', $this->db_opt, array($this, 'validate_options'));

      // XML API required settings.
      add_settings_section(
        'jobamatic_api_settings',
        'API Credential',
        array($this, 'api_credentials_text'),
        'jobamatic'
      );

      add_settings_field(
        'publisher_id',
        'Publisher ID',
        array($this, 'publisher_id_input'),
        'jobamatic',
        'jobamatic_api_settings'
      );
      add_settings_field(
        'domain',
        'Jobamatic domain',
        array($this, 'domain_input'),
        'jobamatic',
        'jobamatic_api_settings'
      );

      // Default search options.
      add_settings_section(
        'jobamatic_default_search',
        'Default Job Search',
        array($this, 'default_search_text'),
        'jobamatic'
      );

      add_settings_field(
        'query',
        'Search query',
        array($this, 'default_query_input'),
        'jobamatic',
        'jobamatic_default_search'
      );

	  add_settings_field(
	    'sort',
        'Sort order',
        array($this, 'default_sort_input'),
        'jobamatic',
        'jobamatic_default_search'
	  );

      add_settings_field(
        'per_page',
        'Results per page',
        array($this, 'default_per_page_input'),
        'jobamatic',
        'jobamatic_default_search'
      );

      add_settings_field(
        'location',
        'Location',
        array($this, 'default_location_input'),
        'jobamatic',
        'jobamatic_default_search'
      );

      add_settings_field(
        'miles',
        'Miles',
        array($this, 'default_miles_input'),
        'jobamatic',
        'jobamatic_default_search'
      );

      // Other fields as needed.

      add_settings_section(
        'jobamatic_other_settings',
        'Miscellaneous Settings',
        array($this, 'other_text'),
        'jobamatic'
      );

      add_settings_field(
        'post_id',
        'Jobs page',
        array($this, 'job_page_input'),
        'jobamatic',
        'jobamatic_other_settings'
      );

      add_settings_field(
        'advanced_search',
        'Enable advanced search?',
        array($this, 'advanced_search_input'),
        'jobamatic',
        'jobamatic_other_settings'
      );

      add_settings_field(
        'attribution',
        '&dagger; Include SimplyHired attribution?',
        array($this, 'attribution_input'),
        'jobamatic',
        'jobamatic_other_settings'
      );

    }

    function api_credentials_text() {
      echo '<p>Enter your Jobamatic <strong>Publisher ID</strong> and <strong>Jobamatic domain</strong> in the fields below.</p>';
      echo '<p>You can obtain this information by logging in to the <a href="https://www.jobamatic.com/a/jbb/partner-login" target="_jobamatic">Jobamatic portal</a> then clicking on the <a href="https://www.jobamatic.com/a/jbb/partner-dashboard-advanced-xml-api" target="_jobamatic">XML API tab</a>.';
    }

    function default_search_text() {
      echo '<p>Enter the default job search criteria in the fields below.</p>';
    }

    function other_text() {
      echo '<p>Miscellaneous settings for the Jobamatic plugin.</p>';
    }

    function publisher_id_input() {
      $options = $this->get_options();

      echo '<input type="text" id="publisher_id" name="' . $this->db_opt . '[publisher_id]" value="' . $options['publisher_id'] . '" size="25" />';
    }

    function domain_input() {
      $options = $this->get_options();

      echo '<input type="text" id="domain" name="' . $this->db_opt . '[domain]" value="' . $options['domain'] . '" size="25" />';
    }

    function default_query_input() {
      $options = $this->get_options();

      echo '<div><input type="text" id="query" name="'.$this->db_opt.'[query]" value="'.$options['query'].'" size="35" /></div>';
      echo '<p style="font-family:Courier, \'Courier New\', mono;">AND - Match all of the terms connected by AND. The default connector for search terms (case sensitive)<br />OR - Match at least on of the terms connected by OR (case sensitive)<br />NOT - Exclude matches on these terms (case sensitive)<br />( ) - Group order of operations</p>';
      echo '<div>';
      echo '<p>The API supports basic Boolean searches as follows. The API also supports these job-related search requests:</p>';
      echo '<code style="font-family:Courier, \'Courier New\', mono;">';
      echo "All the words: Engineering AND Manager<br />
Exact phrase: &quot;Engineering Manager&quot;<br />
At least one of the words: Engineering OR Manager<br />
Without words: Engineering NOT Manager<br />
Job title: title:(Engineering Manager)<br />
Company name: company:(Apple)<br />
Occupation onet: onet:(13-205*)<br />
(Based on O*net, the U.S. Government's Occupational Classification System. <a href=\"http://blog.jobamatic.com/2007/10/smarter-back-fi.html\" target=\"_jobamatic\">Learn more</a>)";
      echo '</code>';
      echo '</div>';
    }

	function default_sort_input() {
		$options = $this->get_options();
		$allowed = array(
			'rd' => 'relevance descending (default)',
			'ra' => 'relevance ascending',
			'dd' => 'last seen date descending',
			'da' => 'last seen date ascending',
			'td' => 'title descending',
			'ta' => 'title ascending',
			'cd' => 'company descending',
			'ca' => 'company ascending',
			'ld' => 'location descending',
			'la' => 'location ascending'
		);

		echo '<select name="'.$this->db_opt.'[sort]" id="sort">';
		foreach($allowed as $key => $value) {
			echo '<option value="'.$key.'"'.($key == $options['sort'] ? ' selected="selected"' : '').'>' . $allowed[$key] . '</option>';
		}
		echo '</select>';
	}

    function default_per_page_input() {
      $options = $this->get_options();

      echo '<div><input type="text" name="'.$this->db_opt.'[per_page]" id="per_page" value="'.$options['per_page'].'" size="10" maxlength="3" /></div>';
      echo '<p>An integer representing the number of results returned. When available, the API will return 10 jobs by default.<br />The API is limited to a maximum of 100 results per request.</p>';
    }

    function default_location_input() {
      $options = $this->get_options();

      echo '<div><input type="text" name="'.$this->db_opt.'[location]" id="location" value="'.$options['location'].'" size="25" /></div>';
      echo '<p>Location can be a zipcode, state, or city-state combination. Currently, there is no support for multiple location search.</p>';
    }

    function default_miles_input() {
      $options = $this->get_options();

      echo '<div><input type="text" name="'.$this->db_opt.'[miles]" id="miles" value="'.$options['miles'].'" size="10" /></div>';
      echo '<p>Miles represents the radius from the zip code, if specified in Location, or an approximate geographical &quot;city center&quot;<br />if only city and state are present. If Miles is not specified, search will default to a radius of 25 miles. For jobs only<br />within a specific city use &quot;<code>exact</code>&quot;.</p>';
    }

    function job_page_input() {
      // Get a listing of WP pages as canidates for the job page.
      $pages = get_pages(array('sort_column' => 'sort_column'));
      $options = $this->get_options();
      $post_id = trim($options['post_id']);
      $page_opts = array();
      $page_opts[] = '<option value="-1"'.(empty($options['post_id']) ? ' selected="selected"' : '').'>Create new page</option>';
      foreach($pages as $page) {
        $page_opts[] = '<option value="' . $page->ID . '"'.($options['post_id'] == $page->ID ? ' selected="selected"' : '').'>' . $page->post_title . '</option>';
      }
      echo '<div><select name="'.$this->db_opt.'[post_id]" id="post_id">'.implode("\n", $page_opts).'</select></div>';
    }

    function advanced_search_input() {
      $options = $this->get_options();
      echo '<div><select name="'.$this->db_opt.'[advanced_search]" id="advanced_search"><option value="0"'.($options['advanced_search'] == 0 ? ' selected="selected"' : '').'>No</option><option value="1"'.($options['advanced_search'] == 1 ? ' selected="selected"' : '').'>Yes</option></select></div>';
      echo '<p>By enabling the advanced search, users will be presented with additional form fields where they can refine their search criteria.</p>';
    }

    function attribution_input() {
      $options = $this->get_options();

      echo '<div><select name="'.$this->db_opt.'[attribution]" id="attribution"><option value="0"'.($options['attribution'] == 0 ? ' selected="selected"' : '').'>No</option><option value="1"'.($options['attribution'] == 1 ? ' selected="selected"' : '').'>Yes</option></select></div>';
      echo '<div style="font-style: italic;color: #666666;margin-top: 6px;margin-bottom: 14px;">
      &dagger; The SimplyHired&reg; <a href="http://www.jobamatic.com/jbb-static/terms-of-service" target="_jobamatic">Terms of Service Agreement</a> states that this must be included on any page that displays SimplyHired data.
      <br />&nbsp;&nbsp;&nbsp;<strong>By disabling this, you knowingly violate the SimplyHired Terms of Service Agreement and the plugin author is not liable for any legal action taken by SimplyHired.</strong>
    </div>';
    }

    /**
     * Validates plugin settings form when submitted.
     *
     * @return array
     */
    function validate_options($input) {
      $valid['publisher_id'] = preg_replace('/[^0-9]/', '', $input['publisher_id']);
      $valid['domain'] = trim($input['domain']);

	  $valid['per_page'] = preg_replace('/[^0-9]/', '', $input['per_page']);
      $valid['location'] = trim($input['location']);
	  $valid['sort'] = $input['sort'];

      $valid['advanced_search'] = intval($input['advanced_search']);
      $valid['attribution'] = intval($input['attribution']);

      if($valid['publisher_id'] != $input['publisher_id']) {
        add_settings_error(
          $this->db_opt . '[publisher_id]',
          'publisher_id_error',
          'Publisher ID can only contain numbers',
          'error'
        );
      }

	  if(trim($input['query']) == '') {
		add_settings_error(
          $this->db_opt . '[query]',
          'query_error',
          'You must enter the default search criteria',
          'error'
        );
		$valid['query'] = '';
	  } else {
	  	$valid['query'] = trim($input['query']);
	  }

      if($valid['per_page'] != $input['per_page']) {
        add_settings_error(
          $this->db_opt . '[per_page]',
          'per_page_error',
          'Results per page can only contain numbers.',
          'error'
        );
      } elseif(intval($valid['per_page']) < 10 || intval($valid['per_page']) > 100) {
        add_settings_error(
          $this->db_opt . '[per_page]',
          'per_page_error',
          'Results per page must be between 10 and 100.',
          'error'
        );
      }

      $m = trim($input['miles']);
      if(is_numeric($m)) {
        $m = intval($m);
        if($m < 1 || $m > 100) {
          $valid['miles'] = '';
          add_settings_error(
            $this->db_opt . '[miles]',
            'miles_error',
            'Miles must be an integer between 1 and 100 <strong>OR</strong> the exact phrase &quot;exact&quot;.',
            'error'
          );
        } else {
          $valid['miles'] = $input['miles'];
        }
      } elseif($m != '') {
        if(strtolower($m) != 'exact') {
          $valid['miles'] = '';
          add_settings_error(
            $this->db_opt . '[miles]',
            'miles_error',
            'Miles must be an integer between 1 and 100 <strong>OR</strong> the exact phrase &quot;exact&quot;.',
            'error'
          );
        } else {
          $valid['miles'] = $m;
        }
      }


      if($input['post_id'] == '-1') {
        // Create a new page for the job posts to appear.
        if($post_id = $this->create_new_page()) {
          $valid['post_id'] = $post_id;
        } else {
          add_settings_error(
            $this->db_opt . '[post_id]',
            'post_id_error',
            'Failed to create new job page.',
            'error'
          );
        }
      } else {
        $valid['post_id'] = $input['post_id'];
      }

      return $valid;
    }

    /**
     * Displays the plugin settings form.
     *
     * @access public
     * @return void
     */
    public function handle_options() {
      $settings = $this->db_opt;
      include_once( $this->plugin_dir . 'includes/jobamatic-options.php');
    }

    /**
     * Creates new WP Page to attach the job listings to.
     *
     * @access private
     * @return integer
     */
    private function create_new_page() {
      global $user_ID;
      // $post_id = get_page_by_title('Jobamatic Jobs');
      $page = array(
        'post_type' => 'page',
        'post_content' => '',
        'post_parent' => 0,
        'post_author' => $user_ID,
        'post_title' => 'Jobamatic Jobs',
        'post_name' => 'jobs',
        'post_status' => 'publish',
        'comment_status' => 'closed',
        'ping_status' => 'closed'
      );
      return wp_insert_post($page);
    }

    function content($text) {
      $options = $this->get_options();
      if(get_the_ID() == $options['post_id']) {
        /**
         * @todo add javascript CSS for jobamatic
         */
        $this->add_frontend_scripts();
		    $this->add_frontend_css();
        $text .= $this->get_search_form($options);
        $text .= "\n" . '<div id="jobamatic-wrap"></div>';
      }
      return $text;
    }

    protected function add_frontend_scripts() {
      wp_register_script( "jobamatic_script", ($this->plugin_url . 'js/jobamatic.js'), array('jquery'));

      $nonce = wp_create_nonce('jobamatic_ajax_request');
      $params = array(
        'ajax_url' => admin_url(('admin-ajax.php?action=do_search&token=' . $nonce)),
      );

      wp_localize_script('jobamatic_script', 'jobamatic', $params);

      wp_enqueue_script('jobamatic_script');
    }

	protected function add_frontend_css() {
		wp_enqueue_style('jobamatic_style', $this->plugin_url . 'css/jobamatic.css', null, null, 'all');
	}

  public function do_search() {
    header('content-type:text/plain');
    if (!wp_verify_nonce( $_REQUEST['token'], 'jobamatic_ajax_request')) {
        die("You are not authorized to access this page.");
    }

	  require_once($this->plugin_dir . 'includes/lib/SimplyHiredJobamaticAPI.php');
	  $options = $this->get_options();
	  $api = new SimplyHiredJobamaticAPI($options['publisher_id'], $options['domain']);

	  /*
	   * Set up the search query and pagination variables.
	   */
	  $pagination = array();
	  $query = $options['query'];
	  $per_page = intval($options['per_page']);
	  $page = 1;

	  foreach($_REQUEST as $key => $value) {
	  	switch($key) {
			case 'q':
				$query = urldecode(trim($_REQUEST['q']));
				$pagination['q'] = rawurlencode($query);
				break;
			case 'ws':
				$per_page = intval($_REQUEST['ws']);
				$pagination['ws'] = $per_page;
				break;
			case 'p':
				$page = intval($_REQUEST['p']);
        // Don't add the page to the pagination array because WP will add that for us.
				break;
			case 'l':
				$location = urldecode(trim($_REQUEST['l']));
				$pagination['l'] = $location;
				break;
			case 'm':
				$miles = intval($_REQUEST['miles']);
				$pagination['m'] = $miles;
				break;
			case 's':
				$sort = $_REQUEST['s'];
				$pagination['s'] = $sort;
				break;
			default:
				// ignore all other variables.
				break;
	  	}
	  }

	  $data = $api->search($query, $per_page, $page, $location, $miles);

	  $template = 'job-results.php';

	  if(file_exists(get_template_directory() . '/' . $template)) {
	  	$template = get_template_directory() . '/' . $template;
	  } elseif( file_exists($this->plugin_dir . 'templates/' . $template)) {
	  	$template = $this->plugin_dir . 'templates/' . $template;
	  } else {
	  	echo '<div class="error">Error: Can not render search results &ndash; no template found.</div>';
		  die();
	  }
	  /*
	   * Set up pagination.
	   */
	  $pager = FALSE;
	  if( $data && $data->getTotalPages() ) {
	  	$params = array(
			 'format' => '?p=%#%',
			 'total' => $data->getTotalPages(),
			 'current' => $data->getCurrentPage(),
			 'prev_next' => TRUE,
			 'prev_text' => __('« Previous'),
			 'next_text' => __('Next »'),
			 'add_args' => $pagination
		  );

		  $pager = paginate_links($params);
	  }

	   ob_start();
	   include_once($template);
	   $return = ob_get_contents();
	   ob_end_clean();
	   print $return;
     die();
  }

  protected function get_search_form() {
    $options = $this->get_options();
    $out = '<form action="'.get_permalink($options['post_id']).'" method="post" name="jobamatic-search-form" id="jobamatic-search-form">';
    $out .= '<div class="jobamatic-field"><label for="jobamatic-query">Search for</label> <input type="text" size="45" name="q" id="jobamatic-query" value="" /></div>';
    if($options['advanced_search'] == 1) {
      $out .= '<div class="advanced-search-wrap">';
      $out .= '<p><a href="javascript:void(0);" id="jobamatic-advanced-options-trigger" class="expandable collapsed">Show advanced search options</a></p>';
      $out .= '<div id="jobamatic-advanced-options-wrap">';
      $out .= '<div><label for="l">Location</label> <input type="text" size="30" name="l" id="jobamatic-location" value="" /></div>';
      $out .= '<div><label for="m">Distance</label> <select name="m" id="jobamatic-miles">';
      for($i = 1; $i <= 100; $i++) {
        if($i % 10 != 0) continue;
        $out .= '<option value="' . $i . '"' . ($i == 1 ? ' selected="selected"' : '') . '>' . $i . '<option>';
      }
      $out .= '</select></div>';
      $out .= '<div><label for="ws">Results per page</label> <select name="ws" id="jobamatic-ws"><option value="10" selected="selected">10</option><option value="20">20</option><option value="30">30</option><option value="50">50</option><option value="75">75</option><option value="100">100</option></select></div>';
      $out .= '<div><label for="s">Sort order</label> <select name="s" id="jobamatic-sort">';

      $allowed = array(
        'rd' => 'relevance descending (default)',
        'ra' => 'relevance ascending',
        'dd' => 'last seen date descending',
        'da' => 'last seen date ascending',
        'td' => 'title descending',
        'ta' => 'title ascending',
        'cd' => 'company descending',
        'ca' => 'company ascending',
        'ld' => 'location descending',
        'la' => 'location ascending'
      );
      $idx = 0;
      foreach($allowed as $key => $value) {
        $out .= '<option value="'.$key.'"'.($idx == 0 ? ' selected="selected"' : '').'>' . $allowed[$key] . '</option>';
        $idx++;
      }
      $out .= '</select></div>';

      $out .= '</div>'; // advanced-search-options-wrap
      $out .= '</div>'; // advanced-search-wrap
    }
    $out .= '<div class="button-wrap"><input type="submit" name="jobamatic-submit" id="jobamatic-submit" value="Search" />&nbsp;<input type="button" name="reset" id="jobamatic-form-reset" value="Reset" /></div>';
    $out .= '</form>';
    return $out;
  }

} //  End class

  $Jobamatic = new Jobamatic();

  if($Jobamatic) {
    register_activation_hook( __FILE__, array(&$Jobamatic, 'install'));
    // Adds an ajax actions.
    add_action('wp_ajax_do_search', array(&$Jobamatic, 'do_search'));
    add_action('wp_ajax_nopriv_do_search', array(&$Jobamatic, 'do_search'));
  }
}
