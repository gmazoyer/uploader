<?php

/*
 * Uploader - Share files for a limited time.
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

require_once 'file.php';
require_once 'utils.php';

/**
 * This class represents an upload that contains files.
 *
 * An upload is defined by its ID, a date of deletion and the files that it
 * contains. The path to the upload and its content can be determined with
 * these characteristics.
 */
final class Upload {
  /**
   * The unique identifier of the upload.
   */
  private $id;

  /**
   * The timestamp after which the upload can be deleted.
   */
  private $deletion_date;

  /**
   * The path to get access to the upload and its content.
   */
  private $path;

  /**
   * The files that are integrated in the upload.
   */
  private $files;

  /**
   * Create a new Upload object with the given parameters.
   *
   * @param string  $id            the ID of the Upload.
   * @param integer $deletion_date the timestamp or base to compute the
   *                               timestamp to schedule the upload deletion.
   * @param string  $path          the base path when the upload will be
   *                               stored.
   * @param boolean $new           defaults to true, if true the timestamp
   *                               will be generated otherwise the timestamp
   *                               is  already considered as correct.
   */
  public function __construct($id, $deletion_date, $path, $new = true) {
    $this->id = $id;

    if ($new) {
      $this->deletion_date = generate_deletion_date($deletion_date);
    } else {
      $this->deletion_date = $deletion_date;
    }

    $this->path = $path.'/'.$this->id;
    if (!file_exists($this->path)) {
      mkdir($this->path, 0700);
    }

    $this->files = array();
  }

  /**
   * Get the ID of the upload.
   *
   * @return string the ID.
   */
  public function get_id() {
    return $this->id;
  }

  /**
   * Get the timestamp from which the upload can be deleted.
   *
   * @return integer the timestamp.
   */
  public function get_deletion_date() {
    return $this->deletion_date;
  }

  /**
   * Get the path from where the upload can be accessed.
   *
   * @return string the path.
   */
  public function get_path() {
    return $this->path;
  }

  /**
   * Get the files that are part of the upload.
   *
   * @return array the files.
   */
  public function get_files() {
    return $this->files;
  }

  /**
   * Get the file in this upload with the given filename.
   *
   * @return File the file or false if none found.
   */
  public function get_file_by_name($filename) {
    foreach ($this->files as $file) {
      if ($file->get_name() === $filename) {
        return $file;
      }
    }

    return false;
  }

  /**
   * Add the given file to the list of files that are part of the upload.
   *
   * @param File $file the file.
   */
  public function add_file($file) {
    $this->files[] = $file;
  }

  /**
   * Delete the files on the files system and the directory.
   */
  public function delete() {
    foreach ($this->files as $file) {
      $file->delete();
    }

    rmdir($this->get_path());
  }
}

// End of upload.php
