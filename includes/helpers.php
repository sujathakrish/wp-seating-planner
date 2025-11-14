<?php
namespace WPSeatingPlanner;
if (!defined('ABSPATH')) exit;
function sp_s(string $s){ return sanitize_text_field($s);}