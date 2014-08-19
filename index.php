<?php

/*
 * Uploader - Share files for a limited time
 * Copyright (C) 2014 Guillaume Mazoyer <gmazoyer@gravitons.in>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software Foundation,
 * Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301  USA
 */

require_once 'includes/config.defaults.php';
require_once 'includes/database.php';
require_once 'includes/file.php';
require_once 'includes/upload.php';
require_once 'includes/utils.php';
require_once 'config.php';

final class Uploader {
  private $config;
  private $db;

  function __construct($config) {
    $this->config = $config;
    $this->db = new Database();

    if (!file_exists($this->config['uploads_directory'])) {
      mkdir($this->config['uploads_directory'], 0700);
    }
  }

  private function render_top() {
    print '<!DOCTYPE html>';
    print '<html lang="en">';
    print '<head>';
    print '<meta charset="utf-8" />';
    print '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
    print '<meta name="viewport" content="width=device-width, initial-scale=1" />';
    print '<title>'.htmlentities($this->config['title']).'</title>';
    print '<link href="libs/bootstrap-3.2.0/css/bootstrap.min.css" rel="stylesheet" />';
    if ($this->config['bootstrap_theme']) {
      print '<link href="libs/bootstrap-3.2.0/css/bootstrap-theme.min.css" rel="stylesheet" />';
    }
    print '<link href="libs/fileinput/css/fileinput.min.css" rel="stylesheet" />';
    print '<link href="css/style.css" rel="stylesheet" />';
    print '</head>';
    print '<body>';
    print '<div class="container">';
    print '<div class="header"><a href="." title="Home">';
    print '<h1>'.htmlentities($this->config['title']).'</h1>';
    print '</a></div>';
    print '<div class="alert alert-danger alert-dismissable" style="display: none;" id="error">';
    print '<button type="button" class="close" aria-hidden="true">&times;</button>';
    print '<strong>Error!</strong>&nbsp;<span id="error-text"></span>';
    print '</div>';
  }


  private function render_bottom() {
    print '<div class="footer">';
    print '</div>';
    print '</div>';
    print '</body>';
    print '<script src="js/jquery-2.1.1.min.js"></script>';
    print '<script src="js/jquery.form.min.js"></script>';
    print '<script src="libs/bootstrap-3.2.0/js/bootstrap.min.js"></script>';
    print '<script src="libs/fileinput/js/fileinput.min.js"></script>';
    print '<script src="js/uploader.js"></script>';
    print '</html>';
  }

  public function add_upload($deletion_date, $files) {
    $accept = true;

    // TODO: check for spammer

    // Generate a new ID
    $id = $this->db->generate_id();

    // Create the upload
    $upload = new Upload($id, $deletion_date,
                         $this->config['uploads_directory']);

    // Associate each file to the upload
    foreach ($files['error'] as $key => $error) {
      if ($error == UPLOAD_ERR_OK) {
        $temp = $files['tmp_name'][$key];
        $name = $files['name'][$key];
        $size = filesize($temp);

        // Check that the file type is allowed
        $info = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($info, $temp);
        if (!in_array($mime, $this->config['allowed_file_types'])) {
          $accept = false;
          $return = array('error' => 'Error unauthorized MIME type ('.$mime.
                                     ') for '.$name.'.<br />Please check the '.
                                     'list of authorized MIME types.');
          break;
        }
        finfo_close($info);

        $file = new File($upload, $name, $mime, $size);
        $file->save($temp);

        $upload->add_file($file);
      }
    }

    if (!$accept) {
      // All files have been rejected
      $upload->delete();
    } else {
      // Save the upload
      $this->db->save_upload($upload);
      // Throw the upload's ID for the Javascript
      $return = array('success' =>  $upload->get_id());
    }

    print json_encode($return);
  }

  public function show_upload($id) {
    $id = SQLite3::escapeString($id);
    $upload = $this->db->get_upload_from_id($id);

    if ($upload === false) {
      header('location: ./noupload');
    } else {
      $this->render_top();

      print '<h3>Available files to download ';
      print '<span class="label label-default">';
      print count($upload->get_files());
      print '</span></h3>';
      print '<div class="list-group">';

      foreach ($upload->get_files() as $file) {
        print '<div class="list-group-item row">';
        print '<div class="col-xs-1">';
        print '<span class="glyphicon glyphicon-file"></span>';
        print '</div>';
        print '<div class="col-xs-8"><h5 class="list-group-item-heading">';
        print $file->get_name();
        print '</h5><p class="list-group-item-text">';
        print '<span class="label label-primary">';
        print $file->get_mime_type();
        print '</span>&nbsp;<span class="label label-success">';
        print format_size($file->get_size());
        print '</span></p></div>';
        print '<div class="col-xs-3">';
        print '<div class="pull-right">';
        print '<a href="./'.$upload->get_id().'/'.$file->get_name();
        print '" class="btn btn-success" role="button">';
        print '<span class="glyphicon glyphicon-save"></span></a>';
        print '</div>';
        print '</div>';
        print '</div>';
      }

      print '</div>';
      print '<div class="row">';
      print '<div class="col-xs-12 col-sm-6 col-md-8">';
      print '<span class="glyphicon glyphicon-time"></span>&nbsp;';
      print 'Files are available until:';
      print '</div>';
      print '<div class="col-xs-6 col-sm-6 col-md-4">';
      print '<div class="pull-right">';
      print '<span class="label label-warning">';
      print date('d/m/Y - h:i:s A', $upload->get_deletion_date());
      print '</span>';
      print '</div>';
      print '</div>';
      print '</div>';

      $this->render_bottom();
    }
  }

  public function send_file($id, $filename) {
    $id = SQLite3::escapeString($id);
    $upload = $this->db->get_upload_from_id($id);

    if ($upload === false) {
      header('location: ./noupload');
    } else {
      $file = $upload->get_file_by_name($filename);

      if ($file === false) {
        header('location: ./nofile');
      } else {
        $path = $file->get_path();
        $mime = $file->get_mime_type();
        $size = $file->get_size();

        if (isset($path) && file_exists($path)) {
          header('Cache-Control: no-cache, must-revalidate');
          header('Cache-Control: post-check=0,pre-check=0');
          header('Cache-Control: max-age=0');
          header('Pragma: no-cache');
          header('Expires: 0');

          header('Content-Type: '.$mime);
          header('Content-Length: '.$size);

          $handle = fopen($path, 'r');
          fpassthru($handle);
          fclose($handle);
        }
      }
    }
  }

  public function error_upload_does_not_exist() {
    $this->render_top();

    print '<div class="alert alert-danger alert-dismissible" role="alert">';
    print '<button type="button" class="close" data-dismiss="alert">';
    print '<span aria-hidden="true">&times;</span><span class="sr-only">Close</span>';
    print '</button><strong>Error!</strong> Sorry there is no upload here :(';

    $this->render_bottom();
  }

  public function error_file_does_not_exist() {
    $this->render_top();

    print '<div class="alert alert-danger alert-dismissible" role="alert">';
    print '<button type="button" class="close" data-dismiss="alert">';
    print '<span aria-hidden="true">&times;</span><span class="sr-only">Close</span>';
    print '</button><strong>Error!</strong> Sorry this file does not exist :(';
    print '</div>';

    $this->render_bottom();
  }

  public function render_upload_form() {
    $this->render_top();

    print '<div class="clearfix">';
    print '<div class="pull-left">';
    print $this->config['description'];
    print '</div>';
    print '<div class="pull-right">';
    print '<button type="button" class="btn btn-info popover-dismiss" data-toggle="popover" title="List of allowed MIME types" data-html=true data-content="<ul>';
    foreach ($this->config['allowed_file_types'] as $type) {
      print '<li>'.$type.'</li>';
    }
    print '</ul>"><span class="glyphicon glyphicon-flag"></span>&nbsp;Accepted Files</button>';
    print '</div>';
    print '</div>';
    print '<form enctype="multipart/form-data" action="." method="post">';
    print '<div class="form-group">';
    print '<label for="expiration">Expiration</label>';
    print '<select class="form-control" id="expiration" name="expiration">';
    print '<option value="600">10 minutes</option>';
    print '<option value="3600">1 hour</option>';
    print '<option value="86400" selected="selected">1 day</option>';
    print '<option value="604800">1 week</option>';
    print '<option value="2629743">1 month</option>';
    print '<option value="-1">Eternal</option>';
    print '</select>';
    print '</div>';
    print '<div class="form-group">';
    print '<label for="files">Select Files</label>';
    print '<input id="files" name="files[]" type="file" multiple="true" />';
    print '</div>';
    print '<input type="text" class="hidden" name="dontlook" placeholder="Do not look!" />';
    print '</form>';
    print '<div class="loading hide">';
    print '<div class="progress">';
    print '<div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 0%" id="progress">';
    print '</div>';
    print '</div>';
    print '</div>';

    $this->render_bottom();
  }

  public function cron() {
    $this->db->delete_old_uploads();
  }
}

$uploader = new Uploader($config);

// Launched from CLI, cron cleanup
if (php_sapi_name() == 'cli') {
  $uploader->cron();
  exit();
}

if (isset($_FILES) && !empty($_FILES) && isset($_POST['expiration'])) {
  $uploader->add_upload($_POST['expiration'], $_FILES['files']);
} else {
  $uri = explode('/', $_SERVER['REQUEST_URI']);

  if (!isset($uri[2]) || empty($uri[2])) {
    $uploader->render_upload_form();
  } else {
    switch ($uri[2]) {
      case 'nofile':
        $uploader->error_file_does_not_exist();
        break;

      case 'noupload':
        $uploader->error_upload_does_not_exist();
        break;

      default:
        if (!isset($uri[3])) {
          $uploader->show_upload($uri[2]);
        } else {
          $uploader->send_file($uri[2], $uri[3]);
        }
        break;
    }
  }
}

// End of index.php
