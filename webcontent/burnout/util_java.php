<?php
/**
 * Created by PhpStorm.
 * User: Dennis
 * Date: 3-4-2017
 * Time: 22:31
 *
 * The compile error "Subscript not allowed on non-array type "float" "
 * indicates that a wrong index is supplied to JAVA_ENCODE or JAVA_DECODE
 */

function _getSeparators() {
	return array_map( function ( $a ) {
		return "_{$a}_";
	}, range( 'A', 'Z' ) );
}
function JAVA_DECODE( $input_var, $target_var, $depth, $output_exists = false, $type = 'float' ) {
	$alphas        = _getSeparators();
	$inputArrayVar = $input_var . "Array" . $depth;
	$outputVar     = $target_var . $depth;
	$targetId = $type . str_repeat( "[]", $depth ) . " $target_var";

	echo "///////////////////// JAVA_DECODE $input_var -> " . ($output_exists ? '' : 'new ') . "$targetId /////////////////////\n";
	echo "String[] $inputArrayVar = $input_var.split(\"{$alphas[$depth - 1]}\");\n";
	echo $type . str_repeat( "[]", $depth ) . " $outputVar = new {$type}[$inputArrayVar.length]" . str_repeat( "[]", $depth - 1 ) . ";\n";

	_JAVA_DECODE( $input_var, $target_var, $depth, $type );

	if($output_exists){
		echo $target_var . " = $outputVar;\n";
	} else {
		echo $targetId . " = $outputVar;\n";
	}
	echo "///////////////////// END JAVA_DECODE /////////////////////\n";
}

function _JAVA_DECODE( $input_var, $target_var, $depth, $type ) {
	$alphas            = _getSeparators();
	$iVar              = 'i' . $depth;
	$inputArrayVar     = $input_var . "Array" . $depth;
	$nextInputArrayVar = $input_var . "Array" . ( $depth - 1 );
	$outputVar         = $target_var . $depth;
	$nextOutputVar     = $target_var . ( $depth - 1 );
	$class             = $type === 'int' ? 'Integer' : ucfirst( $type );
	$parseFunction     = $type === 'String' ? 'valueOf' : "parse" . ucfirst( $type );

	if ( $depth == 1 ) {
		echo "for (int $iVar = 0; $iVar < $inputArrayVar.length; $iVar++) {\n";
		echo "{$outputVar}[$iVar] = $class.$parseFunction({$inputArrayVar}[$iVar]);\n";
		echo "}\n";

		return;
	}

	echo "for (int $iVar = 0; $iVar < $inputArrayVar.length; $iVar++) {\n";
	echo "String[] $nextInputArrayVar = {$inputArrayVar}[{$iVar}].split(\"{$alphas[$depth-2]}\");\n";
	echo $type . str_repeat( "[]", $depth - 1 ) . " $nextOutputVar = new {$type}[$nextInputArrayVar.length]" . str_repeat( "[]", $depth - 2 ) . ";\n";

	_JAVA_DECODE( $input_var, $target_var, $depth - 1, $type );

	echo "{$outputVar}[{$iVar}] = $nextOutputVar;\n";
	echo "}\n";
}

function JAVA_ENCODE( $input_var, $target_var, $depth, $exists=false, $js=false ) {
	echo "///////////////////// JAVA_ENCODE $input_var -> $target_var /////////////////////\n";
	echo ($exists ? "" : "String ") . $target_var . " = \"\";\n";
	_JAVA_ENCODE( $input_var, $target_var, 0, $depth, $js );
	// echo "String $target_var = String.valueOf({$target_var}Builder); \n";
	echo "///////////////////// END_JAVA_ENCODE /////////////////////\n";
}
function _JAVA_ENCODE( $input_var, $target_var, $depth, $maxDepth, $js ) {
	$alphas   = _getSeparators();
	$iVar     = 'i' . $depth;
	$inputVar = $input_var . ( $depth === 0 ? "" : implode( array_map( function ( $i ) {
			return "[i$i]";
		}, range( 0, $depth - 1 ) ) ) );
	if($js) echo "{$target_var} += String.valueOf(\"[\");\n";
	echo "for (int $iVar = 0; $iVar < $inputVar.length; $iVar++) {\n";
	if ( $depth === $maxDepth - 1 ) {
		echo "{$target_var} += String.valueOf({$inputVar}[$iVar]);\n"; // Uses String.valueOf in the backend
	} else {
		_JAVA_ENCODE( $input_var, $target_var, $depth + 1, $maxDepth, $js );
	}
	echo "if($iVar < $inputVar.length - 1) {\n";
	$splitChar = $js ? ', ' : $alphas[$maxDepth - $depth - 1];
	echo "{$target_var} += String.valueOf(\"{$splitChar}\");\n";
	echo "}\n";
	echo "}\n";
	if($js) echo "{$target_var} += String.valueOf(\"]\");\n";
}

function _PHP_print( $array, $type, $depth ) {
	if ( ! is_array( $array ) ) {
		$val = $array;
		if($type === 'float'){
			return $val . "f";
		} else if($type === 'String') {
			// TODO: Maybe replace ' by \'
			$val = str_replace("'", "`", $val);
			if(strpos($val, "'") !== false) throw new Exception(" >'< found in >$val< ");
			return '"' . $val . '"';
		} else {
			return $val;
		}
	}
	$sub = array_map( function ( $el ) use ( $type, $depth ) {
		return _PHP_print( $el, $type, $depth - 1 );
	}, $array );

	return "new {$type}" . str_repeat( "[]", $depth ) . "{" . implode( ",", $sub ) . "}";
}
function PHP_PRINT( $array, $varname, $type = 'float', $exists = false ) {
	if(!isset($array)){
		throw new Exception();
	}
	$depth = array_depth( $array );
	$class = $type . str_repeat( "[]", $depth );
	echo ($exists ? '' : $class . ' ') . "$varname = " . _PHP_print( $array, $type, $depth ) . ";\n";
}
function JS_PRINT($array, $varname, $exists = false){
	echo ($exists ? '' : "String ") . "$varname = \""._JS_PRINT($array)."\";";
}
function _JS_PRINT($array ) {
	if ( ! is_array( $array ) ) {
		$val = $array;
		if(is_numeric($val)){
			return $val;
		}
		if(strpos($val, "'") !== false) throw new Exception(" >'< found in >$val< ");
		if(strpos($val, '"') !== false) throw new Exception(" >\"< found in >$val< ");
		return '\"' . $val . '\"';
	}
	$sub = array_map( function ( $el ) {
		return _JS_PRINT( $el );
	}, $array );

	return "[" . implode( ",", $sub ) . "]";
}