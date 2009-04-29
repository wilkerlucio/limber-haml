<?php

/*
 * Copyright 2009 Limber Framework
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *     http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License. 
 */

class Haml
{
	private $element_stack;
	private $current_indent;
	private $buffer;
	
	private static $AUTOCLOSE = array("br", "hr", "meta", "link");
	
	private static $DOCTYPE = array(
			"strict"       => "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">",
			"transitional" => "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"\n\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">",
			"frameset"     => "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Frameset//EN\"\n\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd\">"
	);
	
	public function parse($data)
	{
		$this->element_stack = array();
		$this->current_indent = 0;
		$this->buffer = "";
		
		$lines = preg_split("(\r\n|\n|\r)", $data);
		
		foreach ($lines as $line_number => $line) {
			$indent = $this->calculate_indent($line);
			
			if ($indent > $this->current_indent) {
				throw new HamlParseException("Invalid identation level at line {$line_number}");
			}
			
			$line = $this->normalize_line($line);
			
			if (preg_match("/^-\s*else/", $line)) {
				$this->current_indent -= 1;
				$this->write($this->php_code("else:"));
				$this->current_indent += 1;
				
				continue;
			}
			
			while ($indent < $this->current_indent) {
				$this->element_pop();
			}
			
			if (preg_match("/^([%#.][a-z#.0-9]+)(\/|=)?(?:\s+\{(.*?)})?(?:\s+(.*))?/i", $line, $matches)) {
				$attributes = array();
				
				preg_match_all("/([%#.])([a-z0-9]+)/i", $matches[1], $operations, PREG_SET_ORDER);
				
				$classes = array();
				$tag = null;
				
				foreach ($operations as $op) {
					$cmd = $op[1];
					$val = $op[2];
					
					switch ($cmd) {
						case '%':
							$tag = $val;
							break;
						case '#':
							$attributes["id"] = "\"{$val}\"";
							break;
						case '.':
							$classes[] = $val;
							break;
					}
				}
				
				if ($tag === null) {
					$tag = "div";
				}
				
				if (count($classes) > 0) {
					$attributes["class"] = '"' . implode(" ", $classes) . '"';
				}
				
				$autoclose = @$matches[2] == '/' || in_array($tag, self::$AUTOCLOSE);
				$attributes_string = isset($matches[3]) ? $matches[3] : null;
				$content = isset($matches[4]) ? $matches[4] : "";
				$multiline = !$autoclose && $this->calculate_indent(@$lines[$line_number + 1]) > $this->current_indent;
				
				if ($matches[2] == '=') {
					$content = $this->php_echo($content);
				}
				
				if ($attributes_string) {
					preg_match_all("/([a-z][a-z-]*)\s*=\s*(('|\")?(?(3).*?\\3|[^\s]+))/i", $attributes_string, $attr_matches, PREG_SET_ORDER);
					
					foreach ($attr_matches as $match) {
						$attributes[$match[1]] = $match[2];
					}
				}
				
				if ($multiline) {
					$this->stack_tag($tag, $attributes);
				} else {
					$this->append_tag($tag, $attributes, $content, $autoclose);
				}
			} elseif (preg_match("/^([=-])(.*)/", $line, $matches)) {
				$code = trim($matches[2]);
				$write = $matches[1] == '=';
				
				if (preg_match("/^(?<statement>if|foreach|while|for)\s*(?<params>.*)/", $code, $code_match)) {
					$statement = $code_match["statement"];
					$params = $code_match["params"];
					
					$this->write($this->php_code("{$statement} ({$params}):"));
					$this->stack_element($this->php_code("end{$statement}"));
				} elseif(preg_match("/^else/", $code)) {
					
				} else {
					$fn = $write ? "php_echo" : "php_code";
					
					$this->write($this->$fn($code));
				}
			} elseif(preg_match("/^!!!\s*(.*)/", $line, $matches)) {
				$type = @$matches[1] ? strtolower($matches[1]) : "transitional";
				
				if (!isset(self::$DOCTYPE[$type])) {
					throw new HamlParseException("Invalid doctype {$type} at line {$line_number}");
				}
				
				$this->write(self::$DOCTYPE[$type]);
			} else {
				$this->write($line);
			}
		}
		
		while (count($this->element_stack) > 0) {
			$this->element_pop();
		}
		
		return trim($this->buffer);
	}
	
	private function php_code($code)
	{
		return "<?php $code ?>";
	}
	
	private function php_echo($code)
	{
		return $this->php_code("echo $code");
	}
	
	private function stack_element($element)
	{
		$this->current_indent += 1;
		$this->element_stack[] = $element;
	}
	
	private function stack_tag($tag, $attributes)
	{
		$attr = $this->build_attributes($attributes);
		
		$this->write("<{$tag}{$attr}>");
		
		$this->stack_element("</{$tag}>");
	}
	
	private function append_tag($tag, $attributes, $content, $autoclose = false)
	{
		$attr = $this->build_attributes($attributes);
		
		if ($autoclose) {
			$this->write("<{$tag}{$attr} />");
		} else {
			$this->write("<{$tag}{$attr}>{$content}</{$tag}>");
		}
	}
	
	private function build_attributes($attributes)
	{
		$attr = "";
		
		foreach ($attributes as $key => $value) {
			$attr .= " {$key}={$value}";
		}
		
		return $attr;
	}
	
	private function element_pop()
	{
		$item = array_pop($this->element_stack);
		$this->current_indent -= 1;
		$this->write($item);
	}
	
	private function write($data)
	{
		$this->buffer .= str_repeat("\t", $this->current_indent);
		$this->buffer .= $data;
		$this->buffer .= "\n";
	}
	
	private function calculate_indent($line)
	{
		if (preg_match("/^(\t+)/", $line, $matches)) {
			return strlen($matches[1]);
		}
		
		return 0;
	}
	
	private function normalize_line($line)
	{
		return trim($line);
	}
}

class HamlParseException extends Exception {}
