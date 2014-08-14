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

  public function add_upload($deletion_date, $files) {
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

        $file = new File($upload, $name);
        $file->save($temp);

        $upload->add_file($file);
      }
    }

    // Save the upload
    $this->db->save_upload($upload);

    // Throw the upload's ID for the Javascript
    print $upload->get_id();
  }

  public function show_upload($id) {
    $id = SQLite3::escapeString($id);
    $upload = $this->db->get_upload_from_id($id);

    if ($upload === false) {
      print '<div class="alert alert-danger alert-dismissible" role="alert">';
      print '<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>';
      print '<strong>Error!</strong> Sorry there is no upload here :(';
      print '</div>';
    } else {
      print '<h3>Available files to download <span class="label label-default">';
      print count($upload->get_files());
      print '</span></h3>';
      print '<div class="list-group">';

      foreach ($upload->get_files() as $file) {
        print '<div class="list-group-item clearfix">';
        print '<span class="glyphicon glyphicon-file"></span>&nbsp;';
        print '<span class="list-group-item-text">';
        print $file->get_name();
        print '</span>';
        print '<span class="list-group-item-btn-dl">';
        print '<a href="./';
        print $file->get_path();
        print '" class="btn btn-success" role="button"><span class="glyphicon glyphicon-save"></span></a>';
        print '</span>';
        print '</div>';
      }

      print '</div>';
      print '<div class="row">';
      print '<div class="col-xs-12 col-sm-6 col-md-8">';
      print '<span class="glyphicon glyphicon-time"></span>&nbsp;';
      print 'Files are available until:';
      print '</div>';
      print '<div class="col-xs-6 col-md-4">';
      print '<span class="label label-warning">';
      print date('d/m/Y - h:i:s A', $upload->get_deletion_date());
      print '</span>';
      print '</div>';
      print '</div>';
    }
  }

  public function render_top() {
    print '<!DOCTYPE html>';
    print '<html lang="en">';
    print '<head>';
    print '<meta charset="utf-8" />';
    print '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
    print '<meta name="viewport" content="width=device-width, initial-scale=1" />';
    print '<title>'.$this->config['title'].'</title>';
    print '<link href="libs/bootstrap-3.2.0/css/bootstrap.min.css" rel="stylesheet" />';
    if ($this->config['bootstrap_theme']) {
      print '<link href="libs/bootstrap-3.2.0/css/bootstrap-theme.min.css" rel="stylesheet" />';
    }
    print '<link href="libs/fileinput/css/fileinput.min.css" rel="stylesheet" />';
    print '<link href="css/style.css" rel="stylesheet" />';
    print '</head>';
    print '<body>';
    print '<div class="container">';
    print '<div class="header">';
    print '<h1>'.$this->config['title'].'</h1>';
    print '</div>';
  }

  public function render_upload_form() {
    print $this->config['description'];
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
  }

  public function render_bottom() {
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
  $uploader->render_top();

  if (isset($_GET['upload'])) {
    $uploader->show_upload($_GET['upload']);
  } else {
    $uploader->render_upload_form();
  }

  $uploader->render_bottom();
}

// End of index.php
