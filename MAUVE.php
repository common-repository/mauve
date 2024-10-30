<?php

/**
 * Plugin Name: MAUVE++ 
 * Description:  The MAUVE++ plugin allows you to check the accessibility of wordpress pages and posts while editing them
 * Author: Giulia Causarano
 * Version: 1.0
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */


/**
* Restrict direct access to the file, for security purpose.
*/


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
* Includes plugin JS file in WP admin and pass data from PHP to localised JS script
*/
 
function mauveplugin_scripts() {
  
  wp_enqueue_script('mauveplugin-script', esc_url(plugin_dir_url( __FILE__ ) . 'assets/js/mauveplugin.js'), array('jquery'), false);
  
  $image_url = esc_url(plugin_dir_url( __FILE__ ) . 'assets/img/done.png');
  $content = wp_kses_post(get_post_field('post_content', get_the_ID(), 'edit'));

  
  wp_add_inline_script( 'mauveplugin-script', 'const mauveplugin_vars =' . wp_json_encode( array(
    'image_url' => esc_url( $image_url ),
    'content' => esc_html( $content ),
  ) ), 'before' );


}

add_action( 'admin_enqueue_scripts', 'mauveplugin_scripts' );


/**
* Includes plugin CSS file and font's libraries in WP admin
*/

function mauveplugin_styles() {
 
  wp_enqueue_style( 'mauveplugin-styles', esc_url(plugin_dir_url( __FILE__ ).'assets/css/mauvepluginstyle.css' ));
  wp_enqueue_style('google-font', esc_url('https://fonts.googleapis.com/css?family=Shadows+Into+Light|Waiting+for+the+Sunrise'));
  wp_enqueue_style( 'material-icons', esc_url('https://fonts.googleapis.com/icon?family=Material+Icons' ));
  wp_enqueue_style('material-symbols-outlined', esc_url('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200'));

}

add_action( 'admin_enqueue_scripts', 'mauveplugin_styles' );


/**
* Insert MAUVE button in post edit page
*/

function mauveplugin_btn_activation()
{
  $autosave = wp_get_post_autosave(get_the_ID());
    if ($autosave) {
        return;
    }
  
  require_once(ABSPATH . 'wp-admin/includes/screen.php');
  $screen = get_current_screen();
  if($screen->id == "post" || $screen->id == "page"){
    ?>
    <form role="form" id="mauveplugin_Form" method="post">
      <?php wp_nonce_field('mauveplugin_btn_action', 'mauveplugin_btn_nonce'); ?>
      <input role="button" type="submit" id="mauveplugin_btn" name="mauveplugin_btn" value="" aria-label="avvia valutazione" />
    </form>
    <?php
    
    if (isset($_POST['mauveplugin_btn'])) {
      if (!isset($_POST['mauveplugin_btn_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mauveplugin_btn_nonce'])), 'mauveplugin_btn_action')) {
        die('Invalid nonce');
      }
      wp_enqueue_script('jquery');
      mauveplugin_insert_metabox();
    }
  }

}
add_action('the_post', 'mauveplugin_btn_activation');



/**
* Insert MAUVE metabox in post edit page
*/

function mauveplugin_insert_metabox(){
  
  function mauveplugin_meta_box()
      { 
        add_meta_box( 'mauveplugin_AccessibilityEvaluation', 'MAUVE++', 'mauveplugin_Report', array('post', 'page'), 'normal', 'default');
      }

  add_action( 'add_meta_boxes', 'mauveplugin_meta_box');
  add_action('add_meta_boxes_classic_editor', 'mauveplugin_meta_box');

 
}


/**
* Callback function to populate MAUVE metabox 
*/

function mauveplugin_Report(){

  /**
  * HTTP Request - START
  */

    $post_id = get_the_ID();

    $content = get_post_field('post_content', $post_id, 'edit');

    
    if(empty($content)){?>
      <h2 class="mauveplugin_empty_content">There is no content to evaluate. Please, insert some content and start the evaluation!<h2>
    <?php
      return;
    }

    $url = 'https://mauve.isti.cnr.it/api/validate/evalreportjson';
    
    $args = array(
      'method' => 'POST',
      'headers' => [
        "Content-Type" => "application/json",
      ],
      'body' => wp_json_encode([ 
        "lang" => "it",
        "sender" => "WebService",
        "pastedHtml" => $content,
        "selectedGuideline" => "wcag21",
        "level_of_Conformance" => "AA",
        "selectedDevice" => "Windows - Chrome"
      ]),
      'cookies' => array(),
    );
   
    $response_post = wp_remote_post($url, $args);

  /**
  * HTTP Request - END
  */

  /**
  * HTTP Response processing - START
  */
   
    if (is_wp_error($response_post)) {
      $error_message = $response_post->get_error_message();
      echo 'Something went wrong: '.esc_html($error_message);
    } else {
    
      $response_body = wp_remote_retrieve_body($response_post);
      
      $post_response = json_decode($response_body);
      
      $post_cont_response = $post_response->results->page_report[0]->gdl;
      
      $arr = array();
     
      foreach ($post_cont_response as $gdl) {
        
        foreach ($gdl->criterion as $criteria) {
          
          foreach ($criteria->checkpoints as $check) {
            
            if (count($check->err) != 0 ) {
             
                $Criterio =  $criteria->idcriterion;
                $Tecnica = $check->id;
                $Sommario = $check->summary;
                $TypeResult = $check->checkpointsresult;
            
              foreach($check->err as $err){
                
                foreach($err->err_info->err_line as $el){
                  
                  if ($el->xPathPointer !== "//html"){
                    $xPatherr= $el->xPathPointer;
                    $errline= $el->line;
                    array_push($arr,array('path'=> $xPatherr, 'criterion'=> $Criterio, 'techniques'=> $Tecnica, 'summary'=> $Sommario, 'result' => $TypeResult, 'line' => $errline));
                  }
                } 
              }
            }
          }
        }
      }
      
      $grouped_data = array_reduce($arr, function ($result, $item) {
       
        $key = $item['techniques'] . '-' . $item['summary'];
       
        if (!isset($result[$key])) {
          
            $result[$key] = array();
            $result[$key][0] = $item['criterion'];
            $result[$key][1] = $item['techniques'];
            $result[$key][2] = $item['summary'];
            $result[$key][3] = $item['result'];
        }
        else if ($item['criterion'] != $result[$key][0]){ 
         
          $position = strrpos($result[$key][0], "-");
          
          if ($position !== false) {
          
              $substring = substr($result[$key][0], $position + 2);
              
              if($item['criterion'] != $substring){
                $result[$key][0] = $result[$key][0].' - '.$item['criterion'];
              }
          } else{
            $result[$key][0] = $result[$key][0].' - '.$item['criterion'];
          }
        }
        
        if (!in_array(array($item['path'], $item['line']), $result[$key])) {
          $result[$key][] = array($item['path'], $item['line']);
        }
        return $result;
      }, array());
      
  /**
  * HTTP Response processing - END
  */  

  /**
  * Rendering of data in HTML structures - START
  */  
      $table= '<div id="mauveplugin_Results">';

      $table.= '<ul class="mauveplugin_nav">';
      $table.= '<li id="mauveplugin_summary_tab" class="mauveplugin_tabs">';
      $table.= '<a class="mauveplugin_summary active" id="mauveplugin_summary_table" data-toggle="tab" href="#mauveplugin_TableSummary"><i class="mauveplugin_material-icons">preview</i> Evaluation Summary (WCAG 2.1)</a>';
      $table.= '</li>';
      $table.= '<li id="mauveplugin_developer_tab" class="mauveplugin_tabs">';
      $table.= '<a class="mauveplugin_summary" id="mauveplugin_source_code" data-toggle="tab" href="#mauveplugin_DeveloperCode"><i class="mauveplugin_material-icons">code</i> Web Developer View</a>';
      $table.= '</li>';
      $table.= '</ul>';

      $table.= '<div class="mauveplugin_contentResults">';

      $table.= '<div id="mauveplugin_TableSummary" class="mauveplugin_tab active" role="tabpanel" >';

    
      $table.= '<table id="mauveplugin_Evaluation">';
      $table.= '<thead>';
      $table.= '<tr>';
      $table.= '<th>Criterion</th>';
      $table.= '<th>Technique</th>';
      $table.= '<th>Summary</th>';
      $table.= '<th>Details</th>';
      $table.= '</tr>';
      $table.= '</thead>';
      $table.= '<tbody>';

     
      $dom = new DOMDocument();
     
      @$dom->loadHTML($content);
      
    
      $xpath = new DOMXPath($dom);

      $Paths=array();
      if(empty($grouped_data)){
        $table.= '<tr id="mauveplugin_emptyrow"><td colspan="4"><h2> Nothing to show. Congratulation, the content of this page is fully accessible!</h2></td></tr>';
      }
   
      foreach ($grouped_data as $el) {
        $table.= '<tr class="mauveplugin_rows">';
      
        if($el[3] == 'N' ){
          $table.= '<td rowspan="1"> <span class="material-symbols-outlined mauveplugin_icons error">cancel</span>'. $el[0] . '</td>';
        }
        else{
        $table.= '<td  rowspan="1"> <span class="material-symbols-outlined mauveplugin_icons warning">warning</span>' . $el['0'] . '</td>';
        }
      
        $table.= '<td rowspan="1">' . $el[1] . '</td>';
       
        $table.= '<td class="mauveplugin_row_summary" rowspan="1">' . $el[2] . '</td>';   
        
        $table.= '<td class="mauveplugin_row_path" rowspan="1">'; 
        $table.='<div class="mauveplugin_details">';
        $lenght=count($el) - 4;
        if($lenght === 1) {
          if($el[3] == 'N' ) {
            $table.= '<p>'.$lenght.' <span class="mauveplugin_errors"> error </span> found';
          }
          else{
            $table.= '<p>'.$lenght.' <span class="mauveplugin_warnings"> warning </span> found';
          }
          
        }
        else { 
          if($el[3] == 'N' ) {
            $table.= '<p>'.$lenght.' <span class="mauveplugin_errors"> errors </span> found';
          }
          else{
            $table.= '<p>'.$lenght.' <span class="mauveplugin_warnings"> warnings </span> found';
          }
          
        }
        $table.= '</p>';
        $table.= '<button data-technique="'.$el[1].'" class="mauveplugin_ShowMore">View more</button>';
        $Technique = array(
          "ARIA" => "https://www.w3.org/WAI/WCAG21/Techniques/aria/",
          "SCR" => "https://www.w3.org/WAI/WCAG21/Techniques/client-side-script/",
          "C" => "https://www.w3.org/WAI/WCAG21/Techniques/css/",
          "F" => "https://www.w3.org/WAI/WCAG21/Techniques/failures/",
          "G" => "https://www.w3.org/WAI/WCAG21/Techniques/general/",
          "H" => "https://www.w3.org/WAI/WCAG21/Techniques/html/",
          "PDF" => "https://www.w3.org/WAI/WCAG21/Techniques/pdf/",
          "SVR" => "https://www.w3.org/WAI/WCAG21/Techniques/server-side-script/",
          "SL" => "https://www.w3.org/WAI/WCAG21/Techniques/silverlight/",
          "SM" => "https://www.w3.org/WAI/WCAG21/Techniques/smil/",
          "T" => "https://www.w3.org/WAI/WCAG21/Techniques/text/"
        );
        foreach ($Technique as $key => $value) {
          if (str_starts_with($el[1], $key)) {
            $href=$Technique[$key].$el[1];
          }
        }
        
        $table.= '
        <div class="mauveplugin_help_container"><span class="mauveplugin_tooltiptext">How to solve</span>
        <a href="'.$href.'" target="_blank" class="mauveplugin_help"><span class="material-symbols-outlined">quick_reference</span>
        </a></div>';
        $table.= '</div>';
        $table.= '<div id="'.$el[1].'" class="mauveplugin_info">';
        $table.= '<table id="mauveplugin_path-list">';
        $table.= '<thead class="mauveplugin_occurrence">';
        $table.= '<tr>';
        $table.= '<th>Element</th>';
        $table.= '<th>View on page</th>';
        $table.= '<th>View on code</th>';
        $table.= '</tr>';
        $table.= '</thead>';
        $table.= '<tbody>';
        for ($i=4; $i<count($el); $i++){
            $value=$el[$i][0];
            $key=$el[$i][1];
            $Paths[$key]=$value;
            
            $elemento = $xpath->query($el[$i][0]);
            foreach($elemento as $node){
              
              $contenuto = $dom->saveHTML($node);
            
              $table.= '<tr> <td class="mauveplugin_view_element">'.htmlspecialchars($contenuto).'</td> <td class="mauveplugin_view_on_page"> <button id="mauveplugin_eye_btn" class="mauveplugin_eye" data-path="'.$el[$i][0].'" data-technique="'.$el[1].'"><span class="material-symbols-outlined">
              visibility
              </span></button> </td> <td> <button id="mauveplugin_code_btn" class="mauveplugin_code" data-id="'.$el[$i][1].'" data-technique="'.$el[1].'"><span class="material-symbols-outlined">
              code
              </span></button> </td> </tr>';
             
            }
          }
        $table.= '</tbody>';
        $table.= '</table>';
        $table.= '</div>';
        $table.= '</td>';
        $table.= '</tr>';
      }
    
      $table.= '</tbody>';
      $table.= '</table>';
      $table.= '</div>';


     
      $table.= '<div id="mauveplugin_DeveloperCode" class="mauveplugin_tab" role="tabpanel" >';
      $table.= '<div id="mauveplugin_dev"><p id="mauveplugin_dev_title">Source code</p></div>';
      $table.= '<pre id="mauveplugin_Code">';
      $table.= '<button id="mauveplugin_show_btn">View results</button>';
      $table.= '<div id="mauveplugin_btn_box">';
      $table.= '<a href="#mauveplugin_TableSummary" id="mauveplugin_back" class="mauveplugin_back_btn">Go back to summary</a>';
      $table.= '<button id="mauveplugin_next_result" class="mauveplugin_nextprev_btn">Next</button>';
      $table.= '<button id="mauveplugin_prev_result" class="mauveplugin_nextprev_btn">Previous</button>';
      $table.= '</div>';
      
     

  
      foreach ($grouped_data as $arrel) {
        for ($i = 4; $i < count($arrel); $i++) {
          $elements = $xpath->query($arrel[$i][0]);
          foreach ($elements as $element) {
            $paragraph = $dom->createElement('p');
            $paragraph->setAttribute('class', 'mauveplugin_MAUVEresult');
            $paragraph->setAttribute('id', 'mauveplugin_line' . $arrel[$i][1] . $arrel[1]);
      
            $icon = $dom->createElement('span');
            $icon->setAttribute('class', 'material-icons mauveplugin_resultType');
            if ($arrel[3] == 'N') {
              $icon->setAttribute('class', 'material-icons mauveplugin_resultType error');
              $icon->nodeValue = 'cancel';
              $paragraph->setAttribute('data-type', 'N');
            } else {
              $icon->setAttribute('class', 'material-icons mauveplugin_resultType warning');
              $icon->nodeValue = 'warning';
              $paragraph->setAttribute('data-type', 'W');
            }
      
            $paragraph->appendChild($icon);
            $summaryResult = $dom->createTextNode('SC ' . $arrel[0] . ' - Tech ' . $arrel[1] . ' : ' . $arrel[2]);
            $paragraph->appendChild($summaryResult);
      
            $element->parentNode->insertBefore($paragraph, $element);
          }
        }
      }
      
      $modifiedHtml = $dom->saveHTML();
      $newModifiedHtml = preg_replace("/(<[^>]+>[^<]*<\/[^>]+>)|(<p class=\"mauveplugin_MAUVEresult\" id=[^>]*><span class=\"material-icons mauveplugin_resultType (warning|error)\">[^<]*<\/span>[^<]*<\/p>)/", "\n$1$2\n", $modifiedHtml);
      $formattedHTML = preg_replace('/\n\s+/', "\n", trim($newModifiedHtml));
      $lines = explode("\n", $formattedHTML);
      
      foreach ($lines as $line) {
        $pattern = '/<p class="mauveplugin_MAUVEresult" id="([^"]+)" data-type="([^"]+)"><span class="material-icons mauveplugin_resultType (warning|error)">[^<]+<\/span>(.*?)<\/p>/';
        if (preg_match($pattern, $line, $matches)) {
          $sub_tech = explode(',1', $matches[1]);
          $id_tech = end($sub_tech);
          foreach ($Technique as $key => $value) {
            if (str_starts_with($id_tech, $key)) {
              $href_code = $Technique[$key] . $id_tech;
            }
          }
          $p = '<p class="mauveplugin_MAUVEresult" id="' . $matches[1] . '" data-type="' . $matches[2] . '"><span class="material-icons mauveplugin_resultType ' . $matches[3] . '">' . ($matches[3] == 'error' ? 'cancel' : 'warning') . '</span>' . '<a href="' . $href_code . '" target="_blank">' . htmlspecialchars($matches[4]) . '</a>' . '</p>';
          $table .= $p . "\n";
        } else {
          $p = '<p>' . htmlspecialchars($line) . '</p>';
          $table .= $p . "\n";
        }
      }
      
      
      

      $table.= '</pre>';
      $table.= '</div>'; 

      $table.= '</div>'; 

      $table.= '</div>';

    
      echo wp_kses_post($table);

  /**
  * Rendering of data in HTML structures - END
  */  
      
      add_post_meta($post_id,'mauveplugin_table',$table);
    }
  
  wp_register_script( 'mauveplugin-script-3', '', array("jquery"), '', true );
  wp_enqueue_script('mauveplugin-script-3');

    
  $mauveplugin_scroll_script = "window.onload = function() {
  
        setTimeout(function() {
          document.getElementById('mauveplugin_btn').style.display='none';
          document.getElementById('mauveplugin_AccessibilityEvaluation').scrollIntoView({ behavior: 'smooth'});
        }, 500);
      };";
  
  wp_add_inline_script('mauveplugin-script-3', $mauveplugin_scroll_script);
  


}



?>