<?
/*
 * Admin pages for FaxYourRepresentative.
 * 
 * Copyright (c) 2004 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: index.php,v 1.4 2004-11-19 12:25:44 francis Exp $
 * 
 */

require_once "../phplib/fyr.php";
require_once "../phplib/admin-fyrqueue.php";
require_once "../../phplib/admin.php";

$pages = array(
    new ADMIN_PAGE_FYR_QUEUE,
    new ADMIN_PAGE_RATTY,
    new ADMIN_PAGE_MAPIT,
    new ADMIN_PAGE_DADEM,
    null, // space separator on menu
    new ADMIN_PAGE_SERVERINFO,
    new ADMIN_PAGE_CONFIGINFO,
    new ADMIN_PAGE_PHPINFO,
);

admin_page_display("FaxYourRepresentative", $pages);

?>
