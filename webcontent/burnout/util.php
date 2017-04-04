<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 3-4-2017
 * Time: 22:18
 */
function array_depth(array $array) {
	$max_depth = 1;

	foreach ($array as $value) {
		if (is_array($value)) {
			$depth = array_depth($value) + 1;

			if ($depth > $max_depth) {
				$max_depth = $depth;
			}
		}
	}

	return $max_depth;
}
include_once 'util_java.php';

$config = json_decode(file_get_contents('config.json'), true);
if(!isset($config)) throw new Exception();

if(false) {// DEBUG JAVA SYNTAX
	echo "\n";
	echo "String string = \"0_A_1_A_2_A_3_B_43_A_5_A_6_A_7_B_8_A_9_A_0_A_11_C_12_A_13_A_14_B_15_A_16_A_17_B_18_A_99\";\n";
	JAVA_DECODE( 'string', 'a3rray', 3, false, 'int' );
	JAVA_ENCODE( "a3rray", "s3tring", 3 );

	JAVA_DECODE( 'string', 'a2rray', 2, false, 'String' );
	JAVA_ENCODE( "a2rray", "s2tring", 2 );

	JAVA_DECODE( 'string', 'a1rray', 1, false, 'String' );
	JAVA_ENCODE( "a1rray", "s1tring", 1 );

	PHP_PRINT($config['default_user_profile'], 'x');
}
