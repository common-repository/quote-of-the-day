<?php
/*
Plugin Name: Quote of the day
Plugin URI: http://www.gigacart.com/quote-of-the-day-widget.html
Description: Quote of the day widget displays random quote from selected categories. There are over 38,000 quotes in various categories (Sport quotes, Humorous quotes, Politician quotes, Motivational quotes, Love quotes and more). You can change widget settings and display quote of the hour or quote of the week. For less stress on the system widget does not use database, but it caches data to a file.
Author: GigaCart
Author URI:http://www.gigacart.com
Version: 1.0.0
*/

/*
 * Class for plugin's widget
 */
class quote_of_the_day_widget {
    // Path to plugin cache directory
    var $cachePath;
    // Cache file variable
    var $cacheFile;
    /*
     * Class constructor function
     */
    function quote_of_the_day_widget() {
        $this->cachePath = ABSPATH . 'wp-content/plugins/quote-of-the-day/cache/';
        $this->cacheFile = 'qotd.widget';
    }
    /*
     * Initiliaze widgets
     */
    function init() {
        // get all widgets options
        if (!$options = get_option('widget_quote_of_the_day'))
            $options = array();

        $widget_ops = array('classname' => 'widget_quote_of_the_day', 'description' => 'Random quote of the day');
        $control_ops = array('width' => 650, 'height' => 100, 'id_base' => 'quote_of_the_day_widget');
        $name = 'Quote of the Day';

        $registered = false;
        foreach (array_keys($options) as $o) {
            if (!isset($options[$o]['title']))
                continue;
            // unique widget id
            $id = "quote_of_the_day_widget-$o";
            //check if the widgets is active
            global $wpdb;
            $sql = "SELECT option_value FROM $wpdb->options WHERE option_name = 'sidebars_widgets' AND option_value like '%".$id."%'";
            $var = $wpdb->get_var( $sql );
            //do this to keep the size of the array down
            if (!$var) unset($options[$o]);

            $registered = true;
            wp_register_sidebar_widget($id, $name, array(&$this, 'sidebar_widget'), $widget_ops, array( 'number' => $o ) );
            wp_register_widget_control($id, $name, array(&$this, 'widget_control'), $control_ops, array( 'number' => $o ) );
        }
        if (!$registered) {
            wp_register_sidebar_widget('quote_of_the_day_widget-1', $name, array(&$this, 'sidebar_widget'), $widget_ops, array( 'number' => -1 ) );
            wp_register_widget_control('quote_of_the_day_widget-1', $name, array(&$this, 'widget_control'), $control_ops, array( 'number' => -1 ) );
        }
        update_option('widget_quote_of_the_day', $options);
    }

    function sidebar_widget($args, $widget_args = 1) {
        extract($args);

        if (is_numeric($widget_args))
            $widget_args = array('number' => $widget_args);
        $widget_args = wp_parse_args($widget_args, array( 'number' => -1 ));
        extract($widget_args, EXTR_SKIP);
        $options_all = get_option('widget_quote_of_the_day');
        if (!isset($options_all[$number]))
            return;

        $options = $options_all[$number];

        //output the quote
        echo $before_widget.$before_title;
        echo $options["title"];
        echo $after_title;
        echo $this->display($options, $number);
        echo $after_widget;
    }

    function widget_control($widget_args = 1) {

        global $wp_registered_widgets;

        static $updated = false;

        //extract widget arguments
        if ( is_numeric($widget_args) )$widget_args = array('number' => $widget_args);
        $widget_args = wp_parse_args($widget_args, array('number' => -1));
        extract($widget_args, EXTR_SKIP);

        $options_all = get_option('widget_quote_of_the_day');
        if (!is_array($options_all))$options_all = array();  

        if (!$updated && !empty($_POST['sidebar'])) {
            $sidebar = (string)$_POST['sidebar'];

            $sidebars_widgets = wp_get_sidebars_widgets();
            if (isset($sidebars_widgets[$sidebar]))
                $this_sidebar =& $sidebars_widgets[$sidebar];
            else
                $this_sidebar = array();

            foreach ($this_sidebar as $_widget_id) {
                if ('widget_quote_of_the_day' == $wp_registered_widgets[$_widget_id]['callback'] && isset($wp_registered_widgets[$_widget_id]['params'][0]['number'])) {
                    $widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
                    if (!in_array("quote_of_the_day_widget-$widget_number", $_POST['widget-id']))
                        unset($options_all[$widget_number]);
                }
            }
            foreach ((array)$_POST['widget_quote_of_the_day'] as $widget_number => $posted) {
                if (!isset($posted['title']) && isset($options_all[$widget_number]))
                    continue;
                // set widget options
                $options = array();
                $options['title'] = $posted['title'];
                $options['category'] = implode(",",$posted['category']);
                $options['display_type'] = $posted['display_type'];
                $options_all[$widget_number] = $options;
            }
            update_option('widget_quote_of_the_day', $options_all);
            $updated = true;
        }
		// default widget options
		$default_options = array(
				'title' => __('Quote of the day', 'quote-of-the-day'),
				'display_type' => 'day'
		);

        if (-1 == $number) {
            $number = '%i%';
            $values = $default_options;
        } else {
            $values = $options_all[$number];
        }

		// widget options form ?>
        <p align="right"><span class="setting-description"><small><?php _e('all settings are for this widget only.', 'quote-of-the-day')?></small></span></p>
        <p><label><strong><?php _e('Title', 'quote-of-the-day')?></strong></label>
		<input class="widefat" id="widget_quote_of_the_day-<?php echo $number; ?>-title" 
        name="widget_quote_of_the_day[<?php echo $number; ?>][title]" type="text" 
        value="<?php echo htmlspecialchars($values['title'], ENT_QUOTES); ?>" />
        </p>
		<p>
			<label for="widget_quote_of_the_day-<?php echo $number; ?>-category"><?php _e('Select category (ctrl + click to select multiple categories)', 'quote-of-the-day'); ?></label><br />
			<select id="widget_quote_of_the_day-<?php echo $number; ?>-category" name="widget_quote_of_the_day[<?php echo $number; ?>][category][]" multiple size="5" style="height:auto">
				<option value="<?php echo $category->cid?>"<?php if (in_array('all', explode(',',$values['category'])) || !$values['category']) echo ' selected="selected"'; ?>><?php _e('All quotes', 'quote-of-the-day'); ?></option>
		        <? foreach($this->getCategories() as $category) {?>
				<option value="<?php echo $category->cid?>"<?php if (in_array($category->cid, explode(',',$values['category']))) echo ' selected="selected"'; ?>><?php echo $category->name; ?></option>
				<? } ?>
			</select>
		</p>
		<p>
			<label for="widget_quote_of_the_day-<?php echo $number; ?>-display_type"><?php _e('Select quote rotation period'); ?></label><br />
            <input type="radio" name="widget_quote_of_the_day[<?php echo $number; ?>][display_type]" value="hour" <?php if ($values['display_type']=='hour') echo ' checked="checked"'; ?> />&nbsp;<?php _e('Quote of the hour', 'quote-of-the-day'); ?><br />
            <input type="radio" name="widget_quote_of_the_day[<?php echo $number; ?>][display_type]" value="day" <?php if ($values['display_type']=='day') echo ' checked="checked"'; ?> />&nbsp;<?php _e('Quote of the day', 'quote-of-the-day'); ?><br />
            <input type="radio" name="widget_quote_of_the_day[<?php echo $number; ?>][display_type]" value="week" <?php if ($values['display_type']=='week') echo ' checked="checked"'; ?> />&nbsp;<?php _e('Quote of the week', 'quote-of-the-day'); ?><br />
		</p>		
        <?php 
	}
    /*
     * Read from cache file if exists, else fecth new data to cache file
     */
    function display($widgetData, $widgetId = "1") {
        global $wp_version;

        $pathToFile = sprintf("%s%s-%s.xml", $this->cachePath, $this->cacheFile, $widgetId);

        $htmlOutput = '';

        // Checking if cache file exist
        if (file_exists($pathToFile) && filesize($pathToFile) > 0) {
            // File does exist, checking if its expired
            if (!$this->checkCacheTime($widgetData['display_type'],filemtime($pathToFile))) {
                // Cache has expired, fetching new data
                $htmlOutput = $this->fetchData($widgetData);
                if ($wp_version >= '2.7') {
                // Saving new data to cache
                if ($htmlOutput['response']['code'] == 200)
                   $this->saveData($htmlOutput['body'], $widgetId);
                } else {
                // Saving new data to cache
                if ($htmlOutput->status == '200')
                   $this->saveData($htmlOutput->results, $widgetId);
                }
                return $this->readCache($widgetData, $widgetId);
            }
            return $this->readCache($widgetData, $widgetId);
        } else {
            // No file found, someone deleted it or first time widget usage
            // Let's create new file with fresh content
            $htmlOutput = $this->fetchData($widgetData);

            if ($wp_version >= '2.7') {
            // Before output, let's save new data to cache
            if ($htmlOutput['response']['code'] == 200)
                $this->saveData($htmlOutput['body'], $widgetId);
            } else {
            // Before output, let's save new data to cache
            if ($htmlOutput->status == '200')
                $this->saveData($htmlOutput->results, $widgetId);
            }
            return $this->readCache($widgetData, $widgetId);
        }
    }
    /*
     * fetch data to cache file
     */
    function fetchData($widgetData) {
        global $wp_version;
        // Set user specified data
        $category = (isset($widgetData['category']))?$widgetData['category']:"all";

        if ($wp_version >= '2.7') {
            $authKey = md5($_SERVER['REQUEST_URI']);
            $client = wp_remote_get('http://www.gigacart.com/development/wp/quotes/getQuote.php?category='.$category.'&auth_key='.$authKey);
        } else {
            echo 'Incorrect WordPress version. At least 2.7 needed';
            return false;
        }

        return $client;
    }
    /*
     * Save data to cache file
     */
    function saveData($data, $widgetId = "1") {
        // Path to cache file
        $pathToFile = sprintf("%s%s-%s.xml", $this->cachePath, $this->cacheFile, $widgetId);
        // Open cache file for writing
        if (!$handle = @fopen($pathToFile, 'w')) {
            echo 'Cannot open file ('.$pathToFile.') Check folder permissions!';
            return false;
        }
        // Write data to cache file
        if (@fwrite($handle, $data) === false) {
            echo 'Cannot write to file ('.$pathToFile.') Check folder permissions!';
            return false;
        }
        // Close cache file
        if (!@fclose($handle)) {
            echo 'Cannot close file ('.$pathToFile.') Check folder permissions!';
            return false;
        }
    }

    function readCache($widgetData, $widgetId = "1") {
        // Get module options
        $quotesOptions = get_option('quote_of_the_day_options');
        // Path to cache file
        $pathToFile = sprintf("%s%s-%s.xml", $this->cachePath, $this->cacheFile, $widgetId);
        // Data variable
        $data = '';
        // Read the data from cache file
        if (!$data = @simplexml_load_file($pathToFile)) {
            echo 'Cannot read file ('.$pathToFile.') Check folder permissions!';
            return false;
        }
        // if XML parsed successfully
        if ($data) {
            $outputLines = '';
            foreach($data->item as $item) {
                // Display quote
                $outputLines .= "<blockquote>";
                $outputLines .= htmlspecialchars($item->text);
                if(isset($item->author)) {
                    $outputLines .= "<br /><address>";
                    $outputLines .= $item->author;
                    if(isset($item->author_data))
                        $outputLines .= " (".$item->author_data.")";
                    $outputLines .= "</address>";
                }
                $outputLines .= "</blockquote>";
                if(isset($item->text_bottom) && isset($item->auth_key) && md5($_SERVER['REQUEST_URI']) == $item->auth_key)
                   $outputLines .= $item->text_bottom;
            }
            return $outputLines;
        } else {
            $errormsg = 'Failed to parse XML file.';
            return false;
        }

    }

    function getCategories() 
    {
        global $wp_version;
        if ($wp_version >= '2.7') {
            $htmlOutput = wp_remote_get('http://www.gigacart.com/development/wp/quotes/getQuoteCategories.php');
            return json_decode($htmlOutput['body']);
        } else {
            echo 'Incorrect WordPress version. At least 2.7 needed';
            return false;
        }
    }

    function checkCacheTime($displayType, $fileCreateTime)
    {
        if ($displayType == "hour") {
            if ($fileCreateTime < mktime(date("H"),0,0))
                return false;
        } elseif ($displayType == "day") {
            if ($fileCreateTime < mktime(0,0,0))
                return false;
        } elseif ($displayType == "week") {
            if ($fileCreateTime < strtotime("Monday"))
                return false;
        }
        return true;
    }
}

$qotdw = new quote_of_the_day_widget();
add_action('widgets_init', array($qotdw, 'init'));

function quote_of_the_day_install() {
	//update_option('quote_of_the_day_options', $options);
}

function quote_of_the_day_uninstall() {
    delete_option('quote_of_the_day_options');
    delete_option('widget_quote_of_the_day');
}

register_activation_hook(__FILE__, 'quote_of_the_day_install');
register_deactivation_hook(__FILE__, 'quote_of_the_day_uninstall');

?>