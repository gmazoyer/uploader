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

function error_upload_does_not_exist() {
  print '<div class="alert alert-danger alert-dismissible" role="alert">';
  print '<button type="button" class="close" data-dismiss="alert">';
  print '<span aria-hidden="true">&times;</span><span class="sr-only">Close</span>';
  print '</button><strong>Error!</strong> Sorry there is no upload here :(';
}

function error_file_does_not_exist() {
  print '<div class="alert alert-danger alert-dismissible" role="alert">';
  print '<button type="button" class="close" data-dismiss="alert">';
  print '<span aria-hidden="true">&times;</span><span class="sr-only">Close</span>';
  print '</button><strong>Error!</strong> Sorry this file does not exist :(';
  print '</div>';
}

function generate_deletion_date($deletion_date) {
  $deletion_date = intval($deletion_date);

  if ($deletion_date > 0) {
    $deletion_date += time();
  }

  return $deletion_date;
}

// End of utils.php
