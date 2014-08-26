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

$config = array(
  'frontpage' => array(
    'title' => 'Uploader',
    'description' => '<p>Select the files to upload and their availability period.<br />You can send a maximum of 10 files with a limit of 2 Gio per upload.</p>',
    'bootstrap_theme' => true
  ),

  'upload' => array(
    'directory' => 'uploads',
    'size' => 2147483648,
    'files' => 10,
    'allowed_types' => array(
      'image/gif',
      'image/jpeg',
      'image/png'
    )
  )
);

// End of config.defaults.php
