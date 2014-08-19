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

require_once 'config.defaults.php';
require_once 'file.php';
require_once 'upload.php';
require_once 'config.php';

/**
 * This class represents a file that is included in an upload.
 *
 * A file is defined by the upload that it is part of and the name of the
 * file. From these two characteristics the code will be able to locate the
 * file on the files system.
 */
final class Database {
  /**
   * The SQLite3 file.
   */
  private $db;

  /**
   * Build a new Database object.
   *
   * It will create the file if necessary.
   */
  public function __construct() {
    $this->db = new SQLite3('uploader.db',
      SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);

    // Cannot open/create the database
    if (is_null($this->db)) {
      if (file_exists('uploader.db')) {
        die('Unable to open database, check permissions.');
      } else {
        die('Unable to create database, check permissions.');
      }
    }

    $this->db->exec('pragma auto_vacuum = 1');
    $this->db->exec(
      'CREATE TABLE IF NOT EXISTS uploads (
        id PRIMARY KEY,
        deletion_date TIMESTAMP
      );'
    );
    $this->db->exec(
      'CREATE TABLE IF NOT EXISTS files (
        id INTEGER PRIMARY KEY,
        upload TEXT,
        name TEXT,
        mime TEXT,
        size INTEGER
      );'
    );
    $this->db->exec(
      'CREATE TABLE IF NOT EXISTS users (
        hash PRIMARY KEY,
        noupload_period TIMESTAMP,
        degree INTEGER
      );'
    );
  }

  /**
   * Close the database properly if it is opened.
   */
  function __destruct() {
    if (!is_null($this->db)) {
      $this->db->close();
    }
  }

  /**
   * Generate an ID that is not already in use.
   *
   * @return $string the ID.
   */
  public function generate_id() {
    do {
      // Generate an ID
      $uniqid = substr(uniqid(), -6);

      // Check if it is already in the database
      $result = $this->db->querySingle(
        "SELECT id FROM uploads WHERE id = '$uniqid';");
    } while (!is_null($result));

    return $uniqid;
  }

  /**
   * Save the given upload to the database.
   *
   * @param Upload the upload to save.
   */
  public function save_upload($upload) {
    $id = $upload->get_id();
    $deletion_date = $upload->get_deletion_date();
    $files = $upload->get_files();

    // Insert the upload
    $this->db->exec(
      "INSERT INTO uploads (id, deletion_date)
       VALUES ('$id', '$deletion_date');");

    // Insert each file
    foreach ($files as $file) {
      $name = $file->get_name();
      $mime = $file->get_mime_type();
      $size = $file->get_size();

      $this->db->exec(
        "INSERT INTO files (id, upload, name, mime, size)
         VALUES (NULL, '$id', '$name', '$mime', '$size');");
    }
  }

  /**
   * Retrieve an upload from the database by its ID.
   *
   * @param  string $id the ID of the upload.
   * @return Upload     the Upload object.
   */
  public function get_upload_from_id($id) {
    global $config;

    // Query the database for the upload
    $request = $this->db->query("SELECT * FROM uploads WHERE id = '$id';");
    if (!($request instanceof Sqlite3Result)) {
      return false;
    }

    // Get the result of the query
    $result = $request->fetchArray();
    if ($result === false) {
      return false;
    }

    // Build the Upload object
    $upload = new Upload($result['id'], $result['deletion_date'],
                         $config['uploads_directory'], false);

    // Query the database for the files
    $request = $this->db->query("SELECT * FROM files WHERE upload = '$id';");
    if (!($request instanceof Sqlite3Result)) {
      return false;
    }

    // Fetch each file
    while ($result = $request->fetchArray()) {
      // And add it to the upload
      $file = new File($upload,         $result['name'],
                       $result['mime'], $result['size']);
      $upload->add_file($file);
    }

    return $upload;
  }

  public function delete_old_uploads() {
    // Remove old users entries
    $this->db->exec(
      "DELETE FROM users WHERE strftime ('%s','now') > noupload_period;");

    // Get old uploads IDs
    $request = $this->db->query(
      "SELECT id FROM uploads
       WHERE deletion_date > 0 AND strftime ('%s','now') > deletion_date;");

    if (!($request instanceof Sqlite3Result)) {
      die('Unable to perform query on the database.');
    }

    while ($result = $request->fetchArray()) {
      $id = $result['id'];

      // Remove the upload
      $upload = $this->get_upload_from_id($id);
      $upload->delete();

      // Cleanup the database entries
      $this->db->query("DELETE FROM uploads WHERE id ='$id';");
      $this->db->query("DELETE FROM files WHERE upload = '$id';");
    }
  }
}

// End of database.php
