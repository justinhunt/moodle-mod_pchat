<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/06/26
 * Time: 13:16
 */

namespace mod_pchat\output;

use \mod_pchat\constants;

class report_renderer extends \plugin_renderer_base
{

    public function render_reportmenu($moduleinstance, $cm)
    {

        $basic = new \single_button(
            new \moodle_url(constants::M_URL . '/reports.php', array('report' => 'basic', 'id' => $cm->id, 'n' => $moduleinstance->id)),
            get_string('basicreport', constants::M_COMPONENT), 'get');

        $buttons =[];
        $attempts = new \single_button(
            new \moodle_url(constants::M_URL . '/reports.php', array('report' => 'attempts', 'id' => $cm->id, 'n' => $moduleinstance->id)),
            get_string('attemptsreport', constants::M_COMPONENT), 'get');
        $buttons[] = $this->render($attempts);

        $detailedattempts = new \single_button(
                new \moodle_url(constants::M_URL . '/reports.php', array('report' => 'detailedattempts', 'id' => $cm->id, 'n' => $moduleinstance->id)),
                get_string('detailedattemptsreport', constants::M_COMPONENT), 'get');
        $buttons[] = $this->render($detailedattempts);

        $classprogress = new \single_button(
                new \moodle_url(constants::M_URL . '/reports.php', array('report' => 'classprogress', 'id' => $cm->id, 'n' => $moduleinstance->id)),
                get_string('classprogressreport', constants::M_COMPONENT), 'get');
        $buttons[] = $this->render($classprogress);

        $myprogress = new \single_button(
                new \moodle_url(constants::M_URL . '/reports.php', array('report' => 'myprogress', 'id' => $cm->id, 'n' => $moduleinstance->id)),
                get_string('myprogressreport', constants::M_COMPONENT), 'get');
        $buttons[] = $this->render($myprogress);

        $myattempts = new \single_button(
                new \moodle_url(constants::M_URL . '/reports.php', array('report' => 'myattempts', 'id' => $cm->id, 'n' => $moduleinstance->id)),
                get_string('myattempts', constants::M_COMPONENT), 'get');
        $buttons[] = $this->render($myattempts);

        $downloadaudio = new \single_button(
                new \moodle_url(constants::M_URL . '/reports.php', array('report' => 'downloadaudio', 'id' => $cm->id, 'n' => $moduleinstance->id)),
                get_string('downloadaudio', constants::M_COMPONENT), 'get');
        $buttons[] = $this->render($downloadaudio);


        $ret = \html_writer::div(  implode("&nbsp;&nbsp;",$buttons),  constants::M_CLASS . '_listbuttons');

        return $ret;
    }
    public function render_hiddenaudioplayer() {
        $audioplayer = \html_writer::tag('audio', '',
                array('src' => '', 'id' => constants::M_HIDDEN_PLAYER, 'class' => constants::M_HIDDEN_PLAYER));
        return $audioplayer;
    }

    public function render_reporttitle_html($course, $username)
    {
        $ret = $this->output->heading(format_string($course->fullname), 2);
        $ret .= $this->output->heading(get_string('reporttitle', constants::M_COMPONENT, $username), 3);
        return $ret;
    }

    public function render_empty_section_html($sectiontitle)
    {
        global $CFG;
        return $this->output->heading(get_string('nodataavailable', constants::M_COMPONENT), 3);
    }

    public function render_exportbuttons_html($cm, $formdata, $showreport)
    {
        //convert formdata to array
        $formdata = (array)$formdata;
        $formdata['id'] = $cm->id;
        $formdata['report'] = $showreport;
        $formdata['format'] = 'csv';
        $excel = new \single_button(
            new \moodle_url(constants::M_URL . '/reports.php', $formdata),
            get_string('exportexcel', constants::M_COMPONENT), 'get');

        return \html_writer::div($this->render($excel), constants::M_CLASS . '_actionbuttons');
    }

    public function render_grading_exportbuttons_html($cm, $formdata, $action)
    {
        //convert formdata to array
        $formdata = (array)$formdata;
        $formdata['id'] = $cm->id;
        $formdata['action'] = $action;
        $formdata['format'] = 'csv';
        $excel = new \single_button(
            new \moodle_url(constants::M_URL . '/grading.php', $formdata),
            get_string('exportexcel', constants::M_COMPONENT), 'get');

        return \html_writer::div($this->render($excel), constants::M_CLASS . '_actionbuttons');
    }



    public function render_section_csv($sectiontitle, $report, $head, $rows, $fields)
    {

        // Use the sectiontitle as the file name. Clean it and change any non-filename characters to '_'.
        $name = clean_param($sectiontitle, PARAM_FILE);
        $name = preg_replace("/[^A-Z0-9]+/i", "_", trim($name));
        $quote = '"';
        $delim = ",";//"\t";
        $newline = "\r\n";

        header("Content-Disposition: attachment; filename=$name.csv");
        header("Content-Type: text/comma-separated-values");

        //echo header
        $heading = "";
        foreach ($head as $headfield) {
            $heading .= $quote . $headfield . $quote . $delim;
        }
        echo $heading . $newline;

        //echo data rows
        foreach ($rows as $row) {
            $datarow = "";
            foreach ($fields as $field) {
                $datarow .= $quote . $row->{$field} . $quote . $delim;
            }
            echo $datarow . $newline;
        }
        exit();
    }

    public function render_section_html($sectiontitle, $report, $head, $rows, $fields)
    {
        global $CFG;
        if (empty($rows)) {
            return $this->render_empty_section_html($sectiontitle);
        }

        //set up our table
        $tableattributes = array('class' => 'generaltable ' . constants::M_CLASS . '_table');

        $htmltable = new \html_table();
        $tableid = \html_writer::random_id(constants::M_COMPONENT);
        $htmltable->id = $tableid;
        $htmltable->attributes = $tableattributes;

        $headcells=[];
        foreach ($head as $headcell) {
            $headcells[] = new \html_table_cell($headcell);
        }
        $htmltable->head = $head;

        //set up our table and head attributes
        /*
        $tableattributes = array('class' => 'generaltable ' . constants::M_CLASS . '_table');
        $headrow_attributes = array('class' => constants::M_CLASS . '_headrow');
        $htmltable = new \html_table();
        $htmltable->attributes = $tableattributes;



        $htr = new \html_table_row();
        $htr->attributes = $headrow_attributes;
        foreach ($head as $headcell) {
            $htr->cells[] = new \html_table_cell($headcell);
        }
        $htmltable->data[] = $htr;
          */

        foreach ($rows as $row) {
            $htr = new \html_table_row();
            //set up descrption cell
            $cells = array();
            foreach ($fields as $field) {
                $cell = new \html_table_cell($row->{$field});
                $cell->attributes = array('class' => constants::M_CLASS . '_cell_' . $report . '_' . $field);
                $htr->cells[] = $cell;
            }

            $htmltable->data[] = $htr;
        }
        $html = $this->output->heading($sectiontitle, 4);
        $html .= \html_writer::table($htmltable);

        //if datatables set up datatables
        if(constants::M_USE_DATATABLES) {
            $tableprops = new \stdClass();
            $opts = Array();
            $opts['tableid'] = $tableid;
            $opts['tableprops'] = $tableprops;
            $this->page->requires->js_call_amd(constants::M_COMPONENT . "/datatables", 'init', array($opts));
        }

        return $html;

    }

    function show_reports_footer($moduleinstance, $cm, $formdata, $showreport)
    {
        // print's a popup link to your custom page
        $link = new \moodle_url(constants::M_URL . '/reports.php', array('report' => 'menu', 'id' => $cm->id, 'n' => $moduleinstance->id));
        $ret = \html_writer::link($link, get_string('returntoreports', constants::M_COMPONENT));
        $ret .= $this->render_exportbuttons_html($cm, $formdata, $showreport);
        return $ret;
    }

    function show_perpage_selector($url, $paging)
    {
        $options = array('5' => 5, '10' => 10, '20' => 20, '40' => 40, '80' => 80, '150' => 150);
        $selector = new \single_select($url, 'perpage', $options, $paging->perpage);
        $selector->set_label(get_string('attemptsperpage', constants::M_COMPONENT));
        return $this->render($selector);
    }

    /**
     * Returns HTML to display a single paging bar to provide access to other pages  (usually in a search)
     * @param int $totalcount The total number of entries available to be paged through
     * @param stdclass $paging an object containting sort/perpage/pageno fields. Created in reports.php and grading.php
     * @param string|moodle_url $baseurl url of the current page, the $pagevar parameter is added
     * @return string the HTML to output.
     */
    function show_paging_bar($totalcount, $paging, $baseurl)
    {
        $pagevar = "pageno";
        //add paging params to url (NOT pageno)
        $baseurl->params(array('perpage' => $paging->perpage, 'sort' => $paging->sort));
        return $this->output->paging_bar($totalcount, $paging->pageno, $paging->perpage, $baseurl, $pagevar);
    }


    function show_export_buttons($cm,$formdata,$showreport){
        switch($showreport) {
            case 'grading':
            case 'machinegrading':
                return $this->render_grading_exportbuttons_html($cm, $formdata, $showreport);
            default:
                return $this->render_exportbuttons_html($cm, $formdata, $showreport);
        }
    }

}