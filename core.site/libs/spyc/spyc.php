<?php
/**
 * Spyc -- A Simple PHP YAML Class
 * @version 0.3
 * @author Chris Wanstrath <chris@ozmm.org>
 * @author Vlad Andersen <vlad@oneiros.ru>
 * @link http://spyc.sourceforge.net/
 * @copyright Copyright 2005-2006 Chris Wanstrath
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @package Spyc
 */
/**
 * The Simple PHP YAML Class.
 *
 * This class can be used to read a YAML file and convert its contents
 * into a PHP array.  It currently supports a very limited subsection of
 * the YAML spec.
 *
 * Usage:
 * <code>
 *   $parser = new Spyc;
 *   $array  = $parser->load($file);
 * </code>
 * @package Spyc
 */
class Spyc {

	/**#@+
	 * @access private
	 * @var mixed
	 */
	var $_haveRefs;
	var $_allNodes;
	var $_allParent;
	var $_lastIndent;
	var $_lastNode;
	var $_inBlock;
	var $_isInline;
	var $_dumpIndent;
	var $_dumpWordWrap;
	var $_containsGroupAnchor = false;
	var $_containsGroupAlias = false;
	var $path;
	var $result;
	var $SavedGroups = array();
	var $emptyValue = ""; /* Just set to NULL for http://yaml.org/type/null.html */
	var $eol = NULL;      /* End Of Line will be autodetected */

	/**#@+
	 * @access public
	 * @var mixed
	 */
	var $_nodeId;

	/**
	 * Load YAML into a PHP array statically
	 *
	 * The load method, when supplied with a YAML stream (string or file),
	 * will do its best to convert YAML in a file into a PHP array.  Pretty
	 * simple.
	 *  Usage:
	 *  <code>
	 *   $array = Spyc::YAMLLoad('lucky.yaml');
	 *   print_r($array);
	 *  </code>
	 * @access public
	 * @return array
	 * @param string $input Path of YAML file or string containing YAML
	 */
	function YAMLLoad($input) {
		$Spyc = new Spyc;
		return $Spyc->load($input);
	}

	/**
	 * Dump YAML from PHP array statically
	 *
	 * The dump method, when supplied with an array, will do its best
	 * to convert the array into friendly YAML.  Pretty simple.  Feel free to
	 * save the returned string as nothing.yaml and pass it around.
	 *
	 * Oh, and you can decide how big the indent is and what the wordwrap
	 * for folding is.  Pretty cool -- just pass in 'false' for either if
	 * you want to use the default.
	 *
	 * Indent's default is 2 spaces, wordwrap's default is 40 characters.  And
	 * you can turn off wordwrap by passing in 0.
	 *
	 * @access public
	 * @return string
	 * @param array $array PHP array
	 * @param int $indent Pass in false to use the default, which is 2
	 * @param int $wordwrap Pass in 0 for no wordwrap, false for default (40)
	 */
	function YAMLDump($array,$indent = false,$wordwrap = false) {
		$spyc = new Spyc;
		return $spyc->dump($array,$indent,$wordwrap);
	}


	/**
	 * Dump PHP array to YAML
	 *
	 * The dump method, when supplied with an array, will do its best
	 * to convert the array into friendly YAML.  Pretty simple.  Feel free to
	 * save the returned string as tasteful.yaml and pass it around.
	 *
	 * Oh, and you can decide how big the indent is and what the wordwrap
	 * for folding is.  Pretty cool -- just pass in 'false' for either if
	 * you want to use the default.
	 *
	 * Indent's default is 2 spaces, wordwrap's default is 40 characters.  And
	 * you can turn off wordwrap by passing in 0.
	 *
	 * @access public
	 * @return string
	 * @param array $array PHP array
	 * @param int $indent Pass in false to use the default, which is 2
	 * @param int $wordwrap Pass in 0 for no wordwrap, false for default (40)
	 */
	function dump($array,$indent = false,$wordwrap = false) {
		// Dumps to some very clean YAML.  We'll have to add some more features
		// and options soon.  And better support for folding.

		// New features and options.
		if ($indent === false or !is_numeric($indent)) {
			$this->_dumpIndent = 2;
		} else {
			$this->_dumpIndent = $indent;
		}

		if ($wordwrap === false or !is_numeric($wordwrap)) {
			$this->_dumpWordWrap = 40;
		} else {
			$this->_dumpWordWrap = $wordwrap;
		}

		// New YAML document
		$string = "---\n";

		// Start at the base of the array and move through it.
		foreach ($array as $key => $value) {
			$string .= $this->_yamlize($key,$value,0);
		}
		return $string;
	}

	/**
	 * Attempts to convert a key / value array item to YAML
	 * @access private
	 * @return string
	 * @param $key The name of the key
	 * @param $value The value of the item
	 * @param $indent The indent of the current node
	 */
	function _yamlize($key,$value,$indent) {
		if (is_array($value)) {
			// It has children.  What to do?
			// Make it the right kind of item
			$string = $this->_dumpNode($key,NULL,$indent);
			// Add the indent
			$indent += $this->_dumpIndent;
			// Yamlize the array
			$string .= $this->_yamlizeArray($value,$indent);
		} elseif (!is_array($value)) {
			// It doesn't have children.  Yip.
			$string = $this->_dumpNode($key,$value,$indent);
		}
		return $string;
	}

	/**
	 * Attempts to convert an array to YAML
	 * @access private
	 * @return string
	 * @param $array The array you want to convert
	 * @param $indent The indent of the current level
	 */
	function _yamlizeArray($array,$indent) {
		if (is_array($array)) {
			$string = '';
			foreach ($array as $key => $value) {
				$string .= $this->_yamlize($key,$value,$indent);
			}
			return $string;
		} else {
			return false;
		}
	}

	/**
	 * Returns YAML from a key and a value
	 * @access private
	 * @return string
	 * @param $key The name of the key
	 * @param $value The value of the item
	 * @param $indent The indent of the current node
	 */
	function _dumpNode($key,$value,$indent) {
		// do some folding here, for blocks
		if (strpos($value,"\n") !== false || strpos($value,": ") !== false || strpos($value,"- ") !== false) {
			$value = $this->_doLiteralBlock($value,$indent);
		} else {
			$value  = $this->_doFolding($value,$indent);
		}

		if (is_bool($value)) {
			$value = ($value) ? "true" : "false";
		}

		$spaces = str_repeat(' ',$indent);

		if (is_int($key)) {
			// It's a sequence
			$string = $spaces.'- '.$value."\n";
		} else {
			// It's mapped
			$string = $spaces.$key.': '.$value."\n";
		}
		return $string;
	}

	/**
	 * Creates a literal block for dumping
	 * @access private
	 * @return string
	 * @param $value
	 * @param $indent int The value of the indent
	 */
	function _doLiteralBlock($value,$indent) {
		$exploded = explode("\n",$value);
		$newValue = '|';
		$indent  += $this->_dumpIndent;
		$spaces   = str_repeat(' ',$indent);
		foreach ($exploded as $line) {
			$newValue .= "\n" . $spaces . trim($line);
		}
		return $newValue;
	}

	/**
	 * Folds a string of text, if necessary
	 * @access private
	 * @return string
	 * @param $value The string you wish to fold
	 */
	function _doFolding($value,$indent) {
		// Don't do anything if wordwrap is set to 0
		if ($this->_dumpWordWrap === 0) {
			return $value;
		}

		if (strlen($value) > $this->_dumpWordWrap) {
			$indent += $this->_dumpIndent;
			$indent = str_repeat(' ',$indent);
			$wrapped = wordwrap($value,$this->_dumpWordWrap,"\n$indent");
			$value   = ">\n".$indent.$wrapped;
		}
		return $value;
	}

	/* LOADING FUNCTIONS */

	function load($input) {
		$Source = $this->loadFromSource($input);
		if (empty ($Source)) return array();
		$this->path = array();
		$this->result = array();

		// detect End Of line
		if (!isset($this->eol)) {
			if    (substr($Source[0], -2, 2) === "\r\n") $this->eol = "\r\n";
			else  $this->eol = substr($Source[0], -1, 1);
		}

		// let's go
		$this->Source = $Source;
		$this->line   = -1;

		while (false !== ($line = $this->nextLine())) 
		{
			if (preg_match('/^\s*\t/', $line))
			{
				$input = str_replace(Config::get('project_dir'), '', $input);
                $humanMessage = '���� <span class="example">'.$input.'</span> �� ������ ��������� ���� � ������ �����.';
				throw new JSException('YAML file <b>'.$input.'</b> has tabs. Kill them all!', '', $humanMessage);
			}
			 
			$this->lineIndent = $lineIndent = $this->_getIndent($line);
			$line = $this->stripIndent($line, $lineIndent);

			if ($this->isComment($line)) continue;

			$this->path = $this->getParentPathByIndent($lineIndent);
			$lineArray = $this->_parseLine($line); // can eat more than one line

			$this->addArray($lineArray, $lineIndent);
		}

		return $this->result;
	}

	function loadFromSource ($input) {
		if (!empty($input) && strpos($input, "\n") === false && file_exists($input))
		return file($input);

		$foo = explode("\n",$input);
		foreach ($foo as $k => $_) {
			$foo[$k] = trim ($_, "\r");
		}
		return $foo;
	}

	function nextLine ()
	{
		return ($this->line + 1 < count($this->Source)) ? $this->Source[++$this->line]: false;
	}

	/**
	 * Finds and returns the indentation of a YAML line
	 * @access private
	 * @return int
	 * @param string $line A line from the YAML file
	 */
	function _getIndent($line) {
		if (!preg_match('/^ +/',$line,$match)) return 0;
		if (!empty($match[0])) return strlen ($match[0]);
		return 0;
	}

	/**
	 * Parses YAML code and returns an array for a node
	 * @access private
	 * @return array
	 * @param string $line A line from the YAML file
	 */
	function _parseLine($line) {
		if (!$line) return array();
		$line = trim($line);
		if (!$line) return array();
		$array = array();

		if ($group = $this->nodeContainsGroupAnchor($line)) {
			$this->addGroup($line, $group);
			$line = $this->stripGroup ($line, $group);
		}

		if ($this->startsMappedValue($line))
		return $this->returnMappedValue($line);

		if ($this->isArrayElement($line))
		return $this->returnArrayElement($line);
		 
		if ($this->isPlainArray($line))
		return $this->returnPlainArray($line);

		return $this->returnKeyValuePair($line);

	}



	/**
	 * Finds the type of the passed key, returns the key as the new type.
	 * @access private
	 * @param string $value
	 * @return mixed
	 */
	function _toTypeKey($value) {

		if (strpos($value, '#') !== false)
		$value = trim(preg_replace('/#(.+)$/','',$value));

		if (preg_match('/^("(.*)"|\'(.*)\')$/',$value,$matches)) {
			$value = (string)preg_replace('/(\'\'|\\\\\')/',"'",end($matches));
			$value = preg_replace('/\\\\"/','"',$value);
		} elseif (strtolower($value) == 'null' or $value == '~') {
			$value = null;
		} elseif (preg_match ('/^[0-9]+$/', $value)) {
			$value = (int)$value;
		} elseif (is_numeric($value)) {
			$value = (float)$value;
		} else {
			// Just a normal string, right?

		}


		//  print_r ($value);
		return $value;
	}

	/**
	 * Finds the type of the passed value, returns the value as the new type.
	 * @access private
	 * @param string $value
	 * @return mixed
	 */
	function _toType($value, $inline=false) {

		$symbolsForReference = 'A-z0-9_\-';

		if (strpos($value, '#') !== false)
		$value = trim(preg_replace('/#(.+)$/','',$value));

		if (preg_match('/^("(.*)"|\'(.*)\')$/',$value,$matches)) {
			$value = (string)preg_replace('/(\'\'|\\\\\')/',"'",end($matches));
			$value = preg_replace('/\\\\"/','"',$value);
		} elseif (preg_match('/^\\[(.*)\\]$/',$value,$matches)) {
			// Inline Sequence
			$thing = trim($matches[1]);
			// Propagate value array
			$value  = array();
			// Take out strings sequences and mappings
			if ($thing !== '') {
				$explode = $this->_inlineEscape($thing);

				foreach ($explode as $v) {
					$value[] = $this->_toType($v);
				}
			}
		} elseif (preg_match('/^\*(['.$symbolsForReference.']+)/', $value, $matches)) {
			// It's a alias
			do {
				$groupAlias = $matches[1];
				if (!isset($this->SavedGroups[$groupAlias])) { echo "Bad group name: $groupAlias."; break; }
				$groupPath = $this->SavedGroups[$groupAlias];
				eval ('$value = $this->result' . Spyc::flatten ($groupPath) . ';');
			} while (false);
		} elseif (!$inline && substr($value, 0, 1) !== '{' && ($_p = strpos($value,': '))!==false) {
			// It's a map
			$key   = trim(substr($value, 0, $_p));
			$key   = $this->_toTypeKey($key);
			$value = trim(substr($value, $_p+2 )); unset($_p);
			$value = $this->_toType($value, true);
			$value = array($key => $value);
		} elseif (preg_match("/^{(.*)}$/",$value,$matches)) {
			// Inline Mapping
			$thing = trim($matches[1]);
			// Propogate value array
			$value = array();
			if ($thing !== '') {
				// Take out strings sequences and mappings
				$explode = $this->_inlineEscape($thing);

				foreach ($explode as $v) {
					$SubArr = $this->_toType($v);
					if (empty($SubArr)) continue;
					if (is_array ($SubArr)) {
						$value[key($SubArr)] = $SubArr[key($SubArr)]; continue;
					}
					$value[] = $SubArr;
				}
			}
		} elseif (strtolower($value) == 'null' or $value == '~') {
			$value = null;
		} elseif ($value == '') {
			$value = $this->emptyValue;
		} elseif (preg_match ('/^[0-9]+$/', $value)) {
			$value = (int)$value;
		} elseif (in_array(strtolower($value), array('true', 'on', '+', 'yes', 'y'))) {
			$value = true;
		} elseif (in_array(strtolower($value), array('false', 'off', '-', 'no', 'n'))) {
			$value = false;
		} elseif (is_numeric($value)) {
			$value = (float)$value;
		} elseif ($sbinfo = $this->startsBlockScalar($value)) {
			$value = $this->returnBlockScalar($value, $sbinfo);
		} else {
			// Just a normal string, right?

		}


		//  print_r ($value);
		return $value;
	}

	/**
	 * Used in inlines to check for more inlines or quoted strings
	 * @access private
	 * @return array
	 */
	function _inlineEscape($inline) {
		// There's gotta be a cleaner way to do this...
		// While pure sequences seem to be nesting just fine,
		// pure mappings and mappings with sequences inside can't go very
		// deep.  This needs to be fixed.

		$saved_strings = array();

		// Check for strings
		$regex = '/(?:(")|(?:\'))((?(1)[^"]+|[^\']+))(?(1)"|\')/';
		if (preg_match_all($regex,$inline,$strings)) {
			$saved_strings = $strings[0];
			$inline  = preg_replace($regex,'YAMLString',$inline);
		}
		unset($regex);

		// Check for sequences
		if (preg_match_all('/\\[(.*)\\]/U',$inline,$seqs)) {
			$inline = preg_replace('/\\[(.*)\\]/U','YAMLSeq',$inline);
			$seqs   = $seqs[0];
		}

		// Check for mappings
		if (preg_match_all('/{(.*)}/U',$inline,$maps)) {
			$inline = preg_replace('/{(.*)}/U','YAMLMap',$inline);
			$maps   = $maps[0];
		}

		$explode = explode(', ',$inline);


		// Re-add the sequences
		if (!empty($seqs)) {
			$i = 0;
			foreach ($explode as $key => $value) {
				if (strpos($value,'YAMLSeq') !== false) {
					$explode[$key] = str_replace('YAMLSeq',$seqs[$i],$value);
					++$i;
				}
			}
		}

		// Re-add the mappings
		if (!empty($maps)) {
			$i = 0;
			foreach ($explode as $key => $value) {
				if (strpos($value,'YAMLMap') !== false) {
					$explode[$key] = str_replace('YAMLMap',$maps[$i],$value);
					++$i;
				}
			}
		}


		// Re-add the strings
		if (!empty($saved_strings)) {
			$i = 0;
			foreach ($explode as $key => $value) {
				while (strpos($value,'YAMLString') !== false) {
					$explode[$key] = preg_replace('/YAMLString/',$saved_strings[$i],$value, 1);
					++$i;
					$value = $explode[$key];
				}
			}
		}

		return $explode;
	}

	function addArrayInline ($array, $indent) {
		if (empty ($array)) return false;
		$CommonGroupPath = $this->path;

		foreach ($array as $k => $_) {
			$this->addArray(array($k => $_), $indent);
			$this->path = $CommonGroupPath;
		}
		return true;
	}

	function addArray ($array, $indent) {

		if (count ($array) > 1)
		return $this->addArrayInline ($array, $indent);
		if (empty ($array))
		return false;

		$key = key ($array);
		$value = $array[$key];

		$isMergeKey = $this->isMergeKey($key);

		$tempPath = Spyc::flatten ($this->path);
		eval ('$_arr = $this->result' . $tempPath . ';');

		// Adding string or numeric key to the innermost level or $this->arr.
		if ($key || is_string($key))
		{
			if ($isMergeKey) {
				if (is_array ($_arr)) { $_arr = array_merge($_arr, $value); }
				else { $_arr = $value; }
			} else {
				$_arr[$key] = $value;
			}
		}
		else {
			if (!is_array ($_arr)) { $_arr = array ($value); $key = 0; }
			else { $_arr[] = $value; end ($_arr); $key = key ($_arr); }
		}

		if (!$isMergeKey) $this->path[$indent] = $key;
		eval ('$this->result' . $tempPath . ' = $_arr;');

		if ($this->_containsGroupAnchor) {
			$this->SavedGroups[$this->_containsGroupAnchor] = $this->path;
			$this->_containsGroupAnchor = false;
		}

		return true;
	}


	function flatten ($array) {
		$tempPath = array();
		if (!empty ($array)) {
			foreach ($array as $_) {
				if (!is_int($_)) $_ = "'$_'";
				$tempPath[] = "[$_]";
			}
		}
		//end ($tempPath); $latestKey = key($tempPath);
		$tempPath = implode ('', $tempPath);
		return $tempPath;
	}


	function startsBlockScalar($line) {
		if (strlen($line) === 0) return false;
		if ($line{0} !== '|' && $line{0} !== '>') return false;

		$sbinfo = false;
		if (preg_match ('/^([|>])([0-9]+)?([+-])?$/', $line, $matches)) {
			// >2+
			$sbinfo = array();
			$sbinfo['_']        = $matches[0];
			$sbinfo['type']     = $matches[1];
			$sbinfo['indent']   = isset($matches[2]) && $matches[2] !== '' ? $matches[2] : NULL;
			$sbinfo['chomping'] = isset($matches[3]) && $matches[3] !== '' ? $matches[3] : '';
		} elseif (preg_match ('/^([|>])([+-])?([0-9]+)?$/', $line, $matches)) {
			// >+2
			$sbinfo = array();
			$sbinfo['_']        = $matches[0];
			$sbinfo['type']     = $matches[1];
			$sbinfo['indent']   = isset($matches[3]) && $matches[3] !== '' ? $matches[3] : NULL;
			$sbinfo['chomping'] = isset($matches[2]) && $matches[2] !== '' ? $matches[2] : '';
		}
		return $sbinfo;
	}

	function blockScalarContinues ($line, $sbinfo) {
		if (trim($line, " \r\n") === '') return true;
		if ($this->_getIndent($line) >= $sbinfo['indent']) return true;
		return false;
	}

	function returnBlockScalar ($line, $sbinfo) {
		$line = rtrim ($line, $sbinfo['_'] . "\n");

		// FIXME : 2007-09-15 : lucky
		// here is look ahead for lines. move it to returnLiteralBlock()
		if (!isset($sbinfo['indent'])) {
			// set block indentation from first non empty line
			$i = $this->line; $c = count($this->Source);
			while (++$i < $c  && trim($this->Source[$i], " \r\n") === '') {}
			$sbinfo['indent'] = $i < $c ? $this->_getIndent($this->Source[$i]) : NULL;
		}
		if ($sbinfo['indent'] > $this->lineIndent) { // it's ok
			$lines = $this->returnLiteralBlock($line, $sbinfo);
		} else {
			// this block contains only empty lines, and i can't to detect indent
			$this->line = $i - 1;
			$lines      = array($this->Source[$this->line]);
		}

		$eol = $this->eol;
		$i = count($lines);
		// tail comments
		while (--$i > 0 && $this->isComment(trim($lines[$i]))) { }
		if ($sbinfo['chomping'] === '-') {
			$lines = array_slice($lines, 0, $i + 1);
			if ($i >= 0) $lines[$i] = rtrim($lines[$i], $eol);
		} elseif ($sbinfo['chomping'] === '') {
			$lines = array_slice($lines, 0, $i + 1);
		} /* else $sbinfo['chomping'] == '+' */

		// fold
		if ($sbinfo['type'] === '>') {
			$i++; // count of lines to fold
			while (--$i > 0) {
				$_line = $lines[$i];
				if (strlen($_line) === 0 || $_line === $eol) {
					$lines[$i] = '';
				} else {
					$_prevline = $lines[$i - 1];
					if ($_line{0} !== ' ' && strlen($_prevline) > 0 && $_prevline !== $eol && $_prevline{0} !== ' ') {
						$lines[$i - 1] = rtrim($_prevline, $eol) . ' ' . $_line;
						$lines[$i] = '';
					}
				}
			}
		}

		return $line . implode('', $lines);
	}

	function returnLiteralBlock ($line, $sbinfo) {
		$lines = array();
		while (false !== ($textline = $this->nextLine()) && $this->blockScalarContinues($textline, $sbinfo)) {
			$_line = $this->stripIndent($textline, $sbinfo['indent']);
			// HACK : 2007-09-15 : lucky
			// we must take (wrong indented) empty lines too
			$lines[] = ($_line === false) ? $this->eol : $_line;
		}
		if ($textline !== false) $this->line--;
		return $lines;
	}


	function stripIndent ($line, $indent = -1) {
		if ($indent == -1) $indent = $this->_getIndent($line);
		if ($indent === 0) return $line;
		return substr ($line, $indent);
	}

	function getParentPathByIndent ($indent) {

		if ($indent == 0) return array();

		$linePath = $this->path;
		do {
			end($linePath); $lastIndentInParentPath = key($linePath);
			if ($indent <= $lastIndentInParentPath) array_pop ($linePath);
		} while ($indent <= $lastIndentInParentPath);
		return $linePath;
	}


	function clearBiggerPathValues ($indent) {


		if ($indent == 0) $this->path = array();
		if (empty ($this->path)) return true;

		foreach ($this->path as $k => $_) {
			if ($k > $indent) unset ($this->path[$k]);
		}

		return true;
	}


	function isMergeKey ($key) {
		if (preg_match('/^<</', $key)) return true;
		return false;
	}

	function isComment ($line) {
		if (preg_match('/^#/', $line)) return true;
		$s = trim($line, " \r\n\t");
		if ($s === '' || $s === '---') return true;
		return false;
	}

	function isArrayElement ($line) {
		if (!$line) return false;
		if ($line[0] != '-') return false;
		if (strlen ($line) > 3)
		if (substr($line,0,3) == '---') return false;

		return true;
	}

	function isHashElement ($line) {
		if (!preg_match('/^(.+?):/', $line, $matches)) return false;
		$allegedKey = $matches[1];
		if ($allegedKey) return true;
		//if (substr_count($allegedKey, )
		return false;
	}

	function isLiteral ($line) {
		if ($this->isArrayElement($line)) return false;
		if ($this->isHashElement($line)) return false;
		return true;
	}


	function returnMappedValue ($line) {
		$array = array();
		$key         = trim(substr($line,0,-1));
		$key         = $this->_toTypeKey($key);
		$array[$key] = $this->emptyValue;
		return $array;
	}

	function startsMappedValue ($line) {
		if (preg_match('/^(.*):$/',$line)) return true;
	}

	function isPlainArray ($line) {
		if (preg_match('/^\\[(.*)\\]$/', $line)) return true;
		return false;
	}

	function returnPlainArray ($line) {
		return $this->_toType($line);
	}

	function returnKeyValuePair ($line) {

		$array = array();

		if (preg_match('/^(.+):/',$line,$key)) {
			// It's a key/value pair most likely
			// If the key is in double quotes pull it out
			if (preg_match('/^((["\']).*\\2)\s*:/',$line,$matches)) {
				$value = trim(str_replace($matches[0],'',$line));
				$key   = $matches[1];
			} else {
				// Do some guesswork as to the key and the value
				$explode = explode(':',$line);
				$key     = trim($explode[0]);
				array_shift($explode);
				$value   = trim(implode(':',$explode));
			}

			// Set the type of the value.  Int, string, etc
			$key = $this->_toTypeKey($key);
			$value = $this->_toType($value, true);
			$array[$key] = $value;
		}

		return $array;

	}


	function returnArrayElement ($line) {
		if (strlen($line) <= 1)
		return array($this->emptyValue); // Just a speed optimization for empty element
		$array = array();
		$value   = trim(substr($line,1));
		$value   = $this->_toType($value);
		$array[] = $value;
		return $array;
	}


	function nodeContainsGroupAnchor ($line) {
		$symbolsForReference = 'A-z0-9_\-';
		if (strpos($line, '&') === false) return false; // Please die fast ;-)
		if (preg_match('/^(&['.$symbolsForReference.']+)/', $line, $matches)) return $matches[1];
		if (preg_match('/(&['.$symbolsForReference.']+$)/', $line, $matches)) return $matches[1];
		return false;
	}

	function addGroup ($line, $group) {
		if (substr ($group, 0, 1) == '&') $this->_containsGroupAnchor = substr ($group, 1);
		//print_r ($this->path);
	}

	function stripGroup ($line, $group) {
		$line = trim(str_replace($group, '', $line));
		return $line;
	}


}
?>
