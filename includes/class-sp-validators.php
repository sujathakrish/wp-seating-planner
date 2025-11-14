<?php
namespace WPSeatingPlanner; class SP_Validators { public static function positive_int($v,$d=0){$v=(int)$v; return $v>0?$v:$d;} }