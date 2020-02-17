<?php

/**
 * Description of auto.advanced_search_categories_custom_id: This class is used to support searching information related to Stock By Attributes.  This way reduces the modifications of the includes/modules/pages/advanced_search_results.php file to nothing.
 *
 * @author mc12345678
 *
 *  mc12345678
 */
class zcObserverAdvancedSearchCategoriesCustomId extends base {

  
  private $enabled;
  
  private $cat_array_to_search;
  
  /*
   * This is the observer for the includes/classes/order.php file to support Stock By Attributes when the order is being processed at the end of the purchase.
   */
  function __construct() {
    
    $attachNotifier = array();
//    $attachNotifier[] = 'NOTIFY_HEADER_START_ADVANCED_SEARCH_RESULTS';
    $attachNotifier[] = 'NOTIFY_SEARCH_COLUMNLIST_STRING';
//    $attachNotifier[] = 'NOTIFY_SEARCH_SELECT_STRING';
//    $attachNotifier[] = 'NOTIFY_SEARCH_FROM_STRING';
    $attachNotifier[] = 'NOTIFY_SEARCH_WHERE_STRING';
//    $attachNotifier[] = 'NOTIFY_SEARCH_ORDERBY_STRING';
//    $attachNotifier[] = 'NOTIFY_HEADER_END_ADVANCED_SEARCH_RESULTS';
  
    $this->attach($this, $attachNotifier);

    $this->cat_array_to_search = array();
    // Identify below the category(ies) that are to be the parent in which to
    //   support search of the product's description.  When tested on ZC 1.5.6
    //   and ZC 1.5.7, this specific search was not required to support a quick
    //   search.
//    $this->cat_array_to_search[] = '117';
    
    $this->enabled = false;
  }  
  
//    $attachNotifier[] = 'NOTIFY_HEADER_START_ADVANCED_SEARCH_RESULTS';
function updateNotifyHeaderStartAdvancedSearchResults(&$callingClass, $notifier, $paramArray) {
}

//  1.5.7: $zco_notifier->notify('NOTIFY_SEARCH_COLUMNLIST_STRING');
//    $attachNotifier[] = 'NOTIFY_SEARCH_COLUMNLIST_STRING';
function updateNotifySearchColumnlistString(&$callingClass, $notifier) {
  global $keywords;
  
  if (defined('TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK')) {
    $this->enabled = true;
  }
}

//    $attachNotifier[] = 'NOTIFY_SEARCH_SELECT_STRING';
function updateNotifySearchSelectString(&$callingClass, $notifier, $paramArray) {
}

// ZC 1.5.7: $zco_notifier->notify('NOTIFY_SEARCH_FROM_STRING');
//    $attachNotifier[] = 'NOTIFY_SEARCH_FROM_STRING';
function updateNotifySearchFromString(&$callingClass, $notifier, $paramArray) {
  global $from_str;
  
  if (empty($this->enabled)) {
    return;
  }
  
}

// ZC 1.5.7: $zco_notifier->notify('NOTIFY_SEARCH_WHERE_STRING');
//    $attachNotifier[] = 'NOTIFY_SEARCH_WHERE_STRING';
function updateNotifySearchWhereString(&$callingClass, $notifier, $paramArray) {
  if (empty($this->enabled)) {
    return;
  }
  
  global $db, $where_str, $keywords, $currencies;
  
  // @TODO - possibly add a switch to control how the search is incorporated.
  //   Currently it is OR'd with the existing search, which means that any attempt
  //   to reduce the results of this portion of the search will not really reduce the
  //   overall results by much considering any previous search.  This search could
  //   be standalone; however, the intent is to not take over the existing search
  //   but instead to supplement with the ability to find product with SBA details.
  if (true) {
    $where_str .= " OR ";
  } else {
    $where_str .= " WHERE ";
  }

  $where_str .= "(p.products_status = 1
                 AND p.products_id = pd.products_id
                 AND pd.language_id = :languagesID
                 AND p.products_id = p2c.products_id
                 AND p2c.categories_id = c.categories_id
                 ";

  $where_str = $db->bindVars($where_str, ':languagesID', $_SESSION['languages_id'], 'integer');

  // reset previous selection
  if (!isset($_GET['inc_subcat'])) {
    $_GET['inc_subcat'] = '0';
  }
  if (!isset($_GET['search_in_description'])) {
    $_GET['search_in_description'] = '0';
  }
  $_GET['search_in_description'] = (int)$_GET['search_in_description'];

  if (isset($_GET['categories_id']) && zen_not_null($_GET['categories_id'])) {
    if ($_GET['inc_subcat'] == '1') {
      $subcategories_array = array();
      zen_get_subcategories($subcategories_array, $_GET['categories_id']);
      $where_str .= " AND p2c.products_id = p.products_id
                      AND p2c.products_id = pd.products_id
                      AND (p2c.categories_id = :categoriesID";

      $where_str = $db->bindVars($where_str, ':categoriesID', $_GET['categories_id'], 'integer');

      if (sizeof($subcategories_array) > 0) {
        $where_str .= " OR p2c.categories_id in (";
        for ($i=0, $n=sizeof($subcategories_array); $i<$n; $i++ ) {
          $where_str .= " :categoriesID";
          if ($i+1 < $n) $where_str .= ",";
          $where_str = $db->bindVars($where_str, ':categoriesID', $subcategories_array[$i], 'integer');
        }
        $where_str .= ")";
      }
      $where_str .= ")";
    } else {
      $where_str .= " AND p2c.products_id = p.products_id
                      AND p2c.products_id = pd.products_id
                      AND pd.language_id = :languagesID
                      AND p2c.categories_id = :categoriesID";

      $where_str = $db->bindVars($where_str, ':categoriesID', $_GET['categories_id'], 'integer');
      $where_str = $db->bindVars($where_str, ':languagesID', $_SESSION['languages_id'], 'integer');
    }
  }

  if (isset($_GET['manufacturers_id']) && zen_not_null($_GET['manufacturers_id'])) {
    $where_str .= " AND m.manufacturers_id = :manufacturersID";
    $where_str = $db->bindVars($where_str, ':manufacturersID', $_GET['manufacturers_id'], 'integer');
  }

  if (isset($keywords) && zen_not_null($keywords)) {
    if (zen_parse_search_string(stripslashes($_GET['keyword']), $search_keywords)) {
      $where_str .= " AND (";
      for ($i=0, $n=sizeof($search_keywords); $i<$n; $i++ ) {
        switch ($search_keywords[$i]) {
          case '(':
          case ')':
          case 'and':
          case 'or':
          $where_str .= " " . $search_keywords[$i] . " ";
          break;
          default:
  // SBA added pwas.customid type information to default search code.
          $where_str .= "(pd.products_name LIKE '%:keywords%'
                                           OR p.products_model
                                           LIKE '%:keywords%'
                                           OR m.manufacturers_name
                                           LIKE '%:keywords%'
                                           ";
          $where_str .= "OR p.products_id 
                                           IN (SELECT products_id 
                                                FROM " . TABLE_PRODUCTS_WITH_ATTRIBUTES_STOCK . " 
                                           WHERE customid LIKE '%:keywords%')";

          foreach ($this->cat_array_to_search as $cat_key => $cat_val) {
            $where_str .= "
                           OR (pd.products_description
                            LIKE '%:keywords%'
                           AND p.products_status = 1
                           AND p.products_id = pd.products_id
                           AND pd.language_id = :languagesID
                           AND p.products_id = p2c.products_id
                           AND p2c.categories_id = c.categories_id
                           ";
  
            $where_str = $db->bindVars($where_str, ':languagesID', $_SESSION['languages_id'], 'integer');
  
            $subcategories_array = array();
            zen_get_subcategories($subcategories_array, $cat_val);
            $where_str .= " AND p2c.products_id = p.products_id
                            AND p2c.products_id = pd.products_id
                            AND (p2c.categories_id = :categoriesID";

            $where_str = $db->bindVars($where_str, ':categoriesID', $cat_val, 'integer');

            if (count($subcategories_array) > 0) {
              $where_str .= " OR p2c.categories_id in (";
              for ($i=0, $n=count($subcategories_array); $i<$n; $i++ ) {
                $where_str .= " :categoriesID";
                if ($i+1 < $n) $where_str .= ",";
                $where_str = $db->bindVars($where_str, ':categoriesID', $subcategories_array[$i], 'integer');
              }
              $where_str .= ")";
            }
            $where_str .= ")"; //p2c.categories_id = :categoriesID
            $where_str .= ")"; //pd.products_description
          }

          $where_str = $db->bindVars($where_str, ':keywords', $search_keywords[$i], 'noquotestring');
        
          // conditionally include meta tags in search
          if ((!defined('ADVANCED_SEARCH_INCLUDE_METATAGS') || ADVANCED_SEARCH_INCLUDE_METATAGS == 'true') && strpos($GLOBALS['from_str'], 'mtpd.') !== false) {
              $where_str .= " OR (mtpd.metatags_keywords != '' AND mtpd.metatags_keywords LIKE '%:keywords%')";
              $where_str .= " OR (mtpd.metatags_description != '' AND mtpd.metatags_description LIKE '%:keywords%')";
              $where_str = $db->bindVars($where_str, ':keywords', $search_keywords[$i], 'noquotestring');
          }

          if (isset($_GET['search_in_description']) && ($_GET['search_in_description'] == '1')) {
            $where_str .= " OR pd.products_description
                            LIKE '%:keywords%'";

            $where_str = $db->bindVars($where_str, ':keywords', $search_keywords[$i], 'noquotestring');
          }
          $where_str .= ')';
          break;
        }
      }
      $where_str .= " ))";
    }
  }
  if (!isset($keywords) || $keywords == "") {
    $where_str .= ')';
  }
  if (isset($_GET['alpha_filter_id']) && (int)$_GET['alpha_filter_id'] > 0) {
    $alpha_sort = " and (pd.products_name LIKE '" . chr((int)$_GET['alpha_filter_id']) . "%') ";
    $where_str .= $alpha_sort;
  } else {
    $alpha_sort = '';
    $where_str .= $alpha_sort;
  }
  //die('I SEE ' . $where_str);

  if (isset($_GET['dfrom']) && zen_not_null($_GET['dfrom']) && ($_GET['dfrom'] != DOB_FORMAT_STRING)) {
    $where_str .= " AND p.products_date_added >= :dateAdded";
    $where_str = $db->bindVars($where_str, ':dateAdded', zen_date_raw($dfrom), 'date');
  }

  if (isset($_GET['dto']) && zen_not_null($_GET['dto']) && ($_GET['dto'] != DOB_FORMAT_STRING)) {
    $where_str .= " and p.products_date_added <= :dateAdded";
    $where_str = $db->bindVars($where_str, ':dateAdded', zen_date_raw($dto), 'date');
  }

  $rate = $currencies->get_value($_SESSION['currency']);
  $pfrom = 0.0;
  $pto = 0.0;

  if ($rate) {
    if (!empty($_GET['pfrom'])) {
      $pfrom = (float)$_GET['pfrom'] / $rate;
    }
    if (!empty($_GET['pto'])) {
      $pto = (float)$_GET['pto'] / $rate;
    }
  }

  if (DISPLAY_PRICE_WITH_TAX == 'true') {
    if ($pfrom) {
      $where_str .= " AND (p.products_price_sorter * IF(gz.geo_zone_id IS null, 1, 1 + (tr.tax_rate / 100)) >= :price)";
      $where_str = $db->bindVars($where_str, ':price', $pfrom, 'float');
    }
    if ($pto) {
      $where_str .= " AND (p.products_price_sorter * IF(gz.geo_zone_id IS null, 1, 1 + (tr.tax_rate / 100)) <= :price)";
      $where_str = $db->bindVars($where_str, ':price', $pto, 'float');
    }
  } else {
    if ($pfrom) {
      $where_str .= " and (p.products_price_sorter >= :price)";
      $where_str = $db->bindVars($where_str, ':price', $pfrom, 'float');
    }
    if ($pto) {
      $where_str .= " and (p.products_price_sorter <= :price)";
      $where_str = $db->bindVars($where_str, ':price', $pto, 'float');
    }
  }
}
//    $attachNotifier[] = 'NOTIFY_SEARCH_ORDERBY_STRING';
function updateNotifySearchOrderbyString(&$callingClass, $notifier, $listing_sql) {
}

//    $attachNotifier[] = 'NOTIFY_HEADER_END_ADVANCED_SEARCH_RESULTS';
function updateNotifyHeaderEndAdvancedSearchResults(&$callingClass, $notifier, $keywords) {
}

  
  /*
   * Generic function that is activated when any notifier identified in the observer is called but is not found in one of the above previous specific update functions is encountered as a notifier.
   */
  function update(&$callingClass, $notifier, $paramsArray) {
//    $attachNotifier[] = 'NOTIFY_HEADER_START_ADVANCED_SEARCH_RESULTS';
    if ($notifier == 'NOTIFY_HEADER_START_ADVANCED_SEARCH_RESULTS') {
      $this->updateNotifyHeaderStartAdvancedSearchResults($callingClass, $notifier, $paramsArray);
    }
//    $attachNotifier[] = 'NOTIFY_SEARCH_COLUMNLIST_STRING';
    if ($notifier == 'NOTIFY_SEARCH_COLUMNLIST_STRING') {
      $this->updateNotifySearchColumnlistString($callingClass, $notifier, $paramsArray);
    }
//    $attachNotifier[] = 'NOTIFY_SEARCH_SELECT_STRING';
    if ($notifier == 'NOTIFY_SEARCH_SELECT_STRING') {
      $this->updateNotifySearchSelectString($callingClass, $notifier, $paramsArray);
    }
//    $attachNotifier[] = 'NOTIFY_SEARCH_FROM_STRING';
    if ($notifier == 'NOTIFY_SEARCH_FROM_STRING') {
      $this->updateNotifySearchFromString($callingClass, $notifier, $paramsArray);
    }
//    $attachNotifier[] = 'NOTIFY_SEARCH_WHERE_STRING';
    if ($notifier == 'NOTIFY_SEARCH_WHERE_STRING') {
      $this->updateNotifySearchWhereString($callingClass, $notifier, $paramsArray);
    }
//    $attachNotifier[] = 'NOTIFY_SEARCH_ORDERBY_STRING';
    if ($notifier == 'NOTIFY_SEARCH_ORDERBY_STRING') {
      $this->updateNotifySearchOrderbyString($callingClass, $notifier, $paramsArray);
    }
//    $attachNotifier[] = 'NOTIFY_HEADER_END_ADVANCED_SEARCH_RESULTS';
    if ($notifier == 'NOTIFY_HEADER_END_ADVANCED_SEARCH_RESULTS') {
      $this->updateNotifyHeaderEndAdvancedSearchResults($callingClass, $notifier, $paramsArray);
    }

  } //end update function - mc12345678
} //end class - mc12345678

