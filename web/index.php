<?
/*
 * index.php:
 * Main page of FaxYourRepresentative, where you enter your postcode
 * 
 * Copyright (c) 2004 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: index.php,v 1.43 2005-02-16 09:15:36 chris Exp $
 * 
 */

require_once "../phplib/fyr.php";
require_once "../../phplib/mapit.php";
require_once '../../phplib/dadem.php';

$pc = get_http_var("pc");
fyr_rate_limit(array("postcode" => array($pc, "Postcode that's been typed in")));

$form = '<form action="./" method="get" name="postcodeForm" id="postcodeForm"><div id="postcodebox">' . "\n";
$form .= '<label for="pc"><b>First, type your UK postcode:</b></label>&nbsp;' . "\n";
$form .= '<input type="text" name="pc" value="'.htmlspecialchars($pc).'" id="pc" size="10" maxlength="255">' . "\n";
$form .= '&nbsp;<input type="submit" value="Go">' . "\n";

/* Record referer. We want to pass this onto the queue later, as an anti-abuse
 * measure, so it should be propagated through all the later pages. Obviously
 * this only has value against a naive attacker; also, there is no point in
 * trying to obscure this data. */
$ref = fyr_external_referrer();
if (isset($ref))
    $form .= '<input type="hidden" name="fyr_extref" value="'.htmlentities($ref).'">';
$form .= '</div></form>';

// Validate postcode, and prepare appropriate page
if (isset($_GET['t'])) $template = 'index-'.$_GET['t'];
else $template = "index-index";
$error_message = null;
if ($pc != "" or array_key_exists('pc', $_GET)) {
    /* Test for various special-case postcodes which lie outside the UK. Many
     * of these aren't valid UK postcode formats, so do a special-case test
     * here rather than passing them down to MaPit. See
     * http://en.wikipedia.org/wiki/Postcode#Overseas_Territories */
    $pc2 = preg_replace('/\\s+/', '', strtoupper($pc));
    $pc22 = substr($pc2, 0, 2); $pc23 = substr($pc2, 0, 3);
    $otmap = array(
            'FIQQ1ZZ' => 'falklands',
            'SIQQ1ZZ' => 'southgeorgia',
            'STHL1ZZ' => 'sthelena',
            /* For our purposes, St Helena and Ascension are the same, though
             * they are thousands of miles apart.... */
            'ASCN1ZZ' => 'sthelena',
            'BIQQ1ZZ' => 'antarctica'
        );
    $ot = null;
    if (array_key_exists($pc2, $otmap)) {
        $ot = $otmap[$pc2];
    } elseif ($pc22 == 'JE') {
        $ot = 'jersey';
    } elseif ($pc22 == 'GY') {
        $ot = 'guernsey';
    } elseif ($pc22 == 'IM') {
        $ot = 'isleofman';
    }

    if (isset($ot)) {
        header("Location: about-elsewhere#$ot");
        exit;
    }

    $specialmap = array(
        'GIR0AA' => 'giro',
        'G1R0AA' => 'giro',
        'SANTA1' => 'santa'
    );
    $ft = null;
    if (array_key_exists($pc2, $specialmap)) {
        $ft = $specialmap[$pc2];
    } elseif ($pc22 == 'AM') {
        $ft = 'archers';
    } elseif ($pc22 == 'FX') {
        $ft = 'training';
    } elseif ($pc23 == 'RE1') {
        $ft = 'reddwarf';
    } elseif (preg_match('/^E20[0-9]/', $pc2)) {
        /* NOT /^E20/, as there are valid and non-fictional E2 0.. postcodes. */
        $ft = 'eastenders';
    }

    if (isset($ft)) {
        header("Location: about-special#$ft");
        exit;
    }

    $voting_areas = mapit_get_voting_areas($pc);

    if (!rabx_is_error($voting_areas)) {
        $area_type = get_http_var('a');
        if ($area_type && $area_type == 'WMC') {
            # Special case to only MPs for now
            # If this is generalised by removing above WMC condition,
            # be aware of DIS style URLs. Proper way is to fetch parent
            # voting area, then fetch all children of that
            $area_representatives = dadem_get_representatives(array_values($voting_areas));
            $all_reps = array();
            foreach (array_values($area_representatives) as $rr) {
                $all_reps = array_merge($all_reps, $rr);
            }
            $representatives_info = dadem_get_representatives_info($all_reps);
            $type_reps = array();
            foreach ($representatives_info as $id => $data) {
                if ($data['type'] == $area_type) {
                    $type_reps[] = $id;
                }
            }
            if (count($type_reps) == 1) {
                header('Location: ' . new_url('write', true, 'a', null, 'who', $type_reps[0]));
                exit;
            }
        }
        header('Location: ' . new_url('who', true, 'a', null));
        exit;
    }
    if ($voting_areas->code == MAPIT_BAD_POSTCODE) {
        $error_message = "Sorry, we need your complete UK postcode to identify your elected representatives.";
        $template = "index-advice";
    }
    else if ($voting_areas->code == MAPIT_POSTCODE_NOT_FOUND) {
        $error_message = "We're not quite sure why, but we can't seem to recognise your postcode.";
        $template = "index-advice";
    }
    else {
        template_show_error($voting_areas->text);
    }
}

template_draw($template, array(
        "title" => "Email or fax your Councillors, MP, MEP or MSP plus Welsh or London Assembly Member for free",
        "blurb-top" => <<<END
    <h2>Contact your
Councillors,
<acronym title="Member of Parliament">MP</acronym>, 
<acronym title="Members of the European Parliament">MEPs</acronym>,
<acronym title="Members of the Scottish Parliament">MSPs</acronym>, or
<br>Welsh and London Assembly Members 
for free</h2>
END
,
        "form" => $form, 
        "error" => $error_message
    ));

?>

