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

require_once dirname(__FILE__) . "/../lib/haml.php";

function spec_parse(&$spec, $template, $expected)
{
	$haml = new Haml();
	$html = $haml->parse($template);
	
	$spec($html)->should->be($expected);
}

describe("Haml", function($spec) {
	$spec->context("parsing", function($spec) {
		$spec->it("should parse simple strings", function($spec, $data) {
			$template = "some simple string";
			$expected = "some simple string";
			
			spec_parse($spec, $template, $expected);
		});
		
		$spec->it("should parse tags in single line", function($spec, $data) {
			$template = "%div div content";
			$expected = "<div>div content</div>";
			
			spec_parse($spec, $template, $expected);
		});
		
		$spec->it("should parse tags in single line with selfclose", function($spec, $data) {
			$template = "%div/";
			$expected = "<div />";
			
			spec_parse($spec, $template, $expected);
		});
		
		$spec->it("should parse many tags in single line", function($spec, $data) {
			$template = <<<EOS
%div some content
%span more content
EOS;
			
			$expected = <<<EOS
<div>some content</div>
<span>more content</span>
EOS;
			
			spec_parse($spec, $template, $expected);
		});

		$spec->it("should parse multi-line elements", function($spec, $data) {
			$template = <<<EOS
%div
	this time
	the content will be internal
EOS;

			$expected = <<<EOS
<div>
	this time
	the content will be internal
</div>
EOS;

			spec_parse($spec, $template, $expected);
		});

		$spec->it("should parse nested elements", function($spec, $data) {
			$template = <<<EOS
%div
	this time
	%b im bold
	nested
	%span
		nesting multiline
		%textarea
			alot nesting
and im at end
EOS;

			$expected = <<<EOS
<div>
	this time
	<b>im bold</b>
	nested
	<span>
		nesting multiline
		<textarea>
			alot nesting
		</textarea>
	</span>
</div>
and im at end
EOS;

			spec_parse($spec, $template, $expected);
		});
		
		$spec->it("should close tag in same line if no content is passed", function($spec, $data) {
			$template = <<<EOS
%div
	content
	%script
	more content
	%script
EOS;
			
			$expected = <<<EOS
<div>
	content
	<script></script>
	more content
	<script></script>
</div>
EOS;

			spec_parse($spec, $template, $expected);
		});
		
		$spec->it("should use tag autoclose when passing a slash after tag name", function($spec, $data) {
			$template = <<<EOS
%div
	content
	%textarea/
EOS;

			$expected = <<<EOS
<div>
	content
	<textarea />
</div>
EOS;

			spec_parse($spec, $template, $expected);
		});

		$spec->it("should use automatic autoclose on tags: br, hr, link, img, input and meta", function($spec, $data) {
			$template = <<<EOS
%div
	%meta
	%link
	%br
	some text
	%hr
	more text
	%img
EOS;

			$expected = <<<EOS
<div>
	<meta />
	<link />
	<br />
	some text
	<hr />
	more text
	<img />
</div>
EOS;

			spec_parse($spec, $template, $expected);
		});

		$spec->context("parsing doctype", function($spec) {
			$spec->it("should generate transitional by default", function($spec, $data) {
				$template  = "!!!";
				$expected  = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"' . "\n";
				$expected .= '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
				
				spec_parse($spec, $template, $expected);
			});
			$spec->it("should generate strict doctype", function($spec, $data) {
				$template  = "!!! Strict";
				$expected  = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"' . "\n";
				$expected .= '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
				
				spec_parse($spec, $template, $expected);
			});
			$spec->it("should generate frameset doctype", function($spec, $data) {
				$template  = "!!! Frameset";
				$expected  = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"' . "\n";
				$expected .= '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">';
				
				spec_parse($spec, $template, $expected);
			});
		});
		
		$spec->context("parsing tag shortcuts", function($spec) {
			$spec->it("should create a div with ID when passing #", function($spec, $data) {
				$template = "#content";
				$expected = '<div id="content"></div>';
				
				spec_parse($spec, $template, $expected);
			});
			
			$spec->it("should create a div with class when passing .", function($spec, $data) {
				$template = '.red';
				$expected = '<div class="red"></div>';
				
				spec_parse($spec, $template, $expected);
			});
			
			$spec->it("should allow to use many classes (.)", function($spec, $data) {
				$template = '.red.blue';
				$expected = '<div class="red blue"></div>';
				
				spec_parse($spec, $template, $expected);
			});
			
			$spec->it("should accept # and . simultaneos", function($spec, $data) {
				$template = '#content.red.blue';
				$expected = '<div id="content" class="red blue"></div>';
				
				spec_parse($spec, $template, $expected);
			});
			
			$spec->it("should accept # and . simultaneos to help tag construct", function($spec, $data) {
				$template = '%span#content.red.blue';
				$expected = '<span id="content" class="red blue"></span>';
				
				spec_parse($spec, $template, $expected);
			});
			
			$spec->it("should accept _ and - when naming ids", function($spec, $data) {
				$template = '%span#main-content_here';
				$expected = '<span id="main-content_here"></span>';
				
				spec_parse($spec, $template, $expected);
			});
			
			$spec->it("should accept _ and - when naming classes", function($spec, $data) {
				$template = '%span.main-content_here';
				$expected = '<span class="main-content_here"></span>';
				
				spec_parse($spec, $template, $expected);
			});
		});
		
		$spec->context("parsing tag attributes", function($spec) {
			$spec->it("should accept one attribute into tags", function($spec, $data) {
				$template = '%div{id="content"}';
				$expected = '<div id="content"></div>';
	
				spec_parse($spec, $template, $expected);
			});
			
			$spec->it("should accept attributes with tag selfclose", function($spec, $data) {
				$template = '%div {id="content"} /';
				$expected = '<div id="content" />';
	
				spec_parse($spec, $template, $expected);
			});
			
			$spec->it("should accept attributes with php echo", function($spec, $data) {
				$template = '%div{id="content"}= "content"';
				$expected = '<div id="content"><?php echo "content" ?></div>';
	
				spec_parse($spec, $template, $expected);
			});
	
			$spec->it("should accept attribute without quotes", function($spec, $data) {
				$template = '%div {id = content class="many names" rel=external}';
				$expected = '<div id="content" class="many names" rel="external"></div>';
	
				spec_parse($spec, $template, $expected);
			});
	
			$spec->it("should accept many attributes into tags", function($spec, $data) {
				$template = "%div {id='con\"tent' class=\"left'column\"}";
				$expected = '<div id=\'con"tent\' class="left\'column"></div>';

				spec_parse($spec, $template, $expected);
			});
	
			$spec->it("should accept many attributes with contents", function($spec, $data) {
				$template = <<<EOS
%div {id='con"tent' class="true"} some content
%div
	%span {class="internal"}
		with nested
		%b content
EOS;
	
				$expected = <<<EOS
<div id='con"tent' class="true">some content</div>
<div>
	<span class="internal">
		with nested
		<b>content</b>
	</span>
</div>
EOS;
	
				spec_parse($spec, $template, $expected);
			});
		});
		
		$spec->context("parsing php code", function($spec) {
			$spec->it("should print php strings", function($spec, $data) {
				$template = '= "Hello World!"';
				$expected = '<?php echo "Hello World!" ?>';

				spec_parse($spec, $template, $expected);
			});
			
			$spec->it("should print php strings into tags", function($spec, $data) {
				$template = '%div= "Hello World!"';
				$expected = '<div><?php echo "Hello World!" ?></div>';

				spec_parse($spec, $template, $expected);
			});
			
			$spec->it("should execute php code", function($spec, $data) {
				$template = '- $name = "Person"';
				$expected = '<?php $name = "Person" ?>';

				spec_parse($spec, $template, $expected);
			});
			
			$spec->it("should use if statement as block level", function($spec, $data) {
				$template = <<<EOS
%div
	- if 1 != 0
		yes, 1 is not 0
EOS;
				$expected = <<<EOS
<div>
	<?php if (1 != 0): ?>
		yes, 1 is not 0
	<?php endif ?>
</div>
EOS;

				spec_parse($spec, $template, $expected);
			});
			
			$spec->it("should use if/else statement as block level", function($spec, $data) {
				$template = <<<EOS
%div
	- if 1 != 0
		yes, 1 is not 0
	- else
		omg! holly crap!
EOS;
				$expected = <<<EOS
<div>
	<?php if (1 != 0): ?>
		yes, 1 is not 0
	<?php else: ?>
		omg! holly crap!
	<?php endif ?>
</div>
EOS;

				spec_parse($spec, $template, $expected);
			});
			
			$spec->it("should use for statement as block level", function($spec, $data) {
				$template = <<<EOS
%div
	- for \$i = 0; \$i < 10; \$i++
		looping
		= \$i
EOS;
				$expected = <<<EOS
<div>
	<?php for (\$i = 0; \$i < 10; \$i++): ?>
		looping
		<?php echo \$i ?>
	<?php endfor ?>
</div>
EOS;

				spec_parse($spec, $template, $expected);
			});
			
			$spec->it("should use while statement as block level", function($spec, $data) {
				$template = <<<EOS
%div
	- \$i = 0
	- while \$i < 10
		looping
		= \$i
		- \$i++
EOS;
				$expected = <<<EOS
<div>
	<?php \$i = 0 ?>
	<?php while (\$i < 10): ?>
		looping
		<?php echo \$i ?>
		<?php \$i++ ?>
	<?php endwhile ?>
</div>
EOS;

				spec_parse($spec, $template, $expected);
			});
			
			$spec->it("should use foreach statement as block level", function($spec, $data) {
				$template = <<<EOS
%div
	- foreach \$people as \$person
		= \$person->name
EOS;
				$expected = <<<EOS
<div>
	<?php foreach (\$people as \$person): ?>
		<?php echo \$person->name ?>
	<?php endforeach ?>
</div>
EOS;

				spec_parse($spec, $template, $expected);
			});
		});
		
		$spec->context("creating comments", function($spec) {
			$spec->it("should comment single line", function($spec, $data) {
				$template = '/ single line comment';
				$expected = '<!-- single line comment -->';
				
				spec_parse($spec, $template, $expected);
			});
			
			$spec->it("should comment multi line", function($spec, $data) {
				$template = <<<EOS
/ 
	multiline comments
	at multiple lines
EOS;
				$expected = <<<EOS
<!--
	multiline comments
	at multiple lines
-->
EOS;

				spec_parse($spec, $template, $expected);
			});
		});
		
		$spec->context("using filters", function($spec) {
			$spec->it("should apply javascript filter", function($spec, $data) {
				$template = <<<EOS
:javascript
	//some comment
	window.onload = function() {
		alert("ok, you got it!");
	};
EOS;
				$expected = <<<EOS
<script type="text/javascript">
	//some comment
	window.onload = function() {
		alert("ok, you got it!");
	};
</script>
EOS;

				spec_parse($spec, $template, $expected);
			});
			
			$spec->it("should apply php filter", function($spec, $data) {
				$template = <<<EOS
:php
	\$a = "some value";
	\$b = "other";
	\$c = \$a + \$b;
EOS;
				$expected = <<<EOS
<?php
	\$a = "some value";
	\$b = "other";
	\$c = \$a + \$b;
?>
EOS;

				spec_parse($spec, $template, $expected);
			});
			
			$spec->it("should apply css filter", function($spec, $data) {
				$template = <<<EOS
:css
	body {
		background: #fff;
	}
EOS;
				$expected = <<<EOS
<style type="text/css">
	body {
		background: #fff;
	}
</style>
EOS;

				spec_parse($spec, $template, $expected);
			});
		});
			
		$spec->it("should work full integrated features", function($spec, $data) {
			$template = <<<EOS
!!! Strict
%html
	%head
		%title Title of Page
		%meta {http-equiv="Content-Type" content="text/html; charset=utf-8"}
		:javascript
			window.onload = function() {
				alert('loaded');
			};
	%body
		#all
			.bg
				- if \$user
					Welcome
					= \$user
				#ct
					page content
					
					.requests Your requests:
					
					- foreach \$user->requests as \$request
						.request= \$request
					
					.box.logo
						%h1
							%a {href="http://some_page" title="Page"} Page
		#bottom
			%h3 Thanks for visit
EOS;
			
			$expected = <<<EOS
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
	<head>
		<title>Title of Page</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<script type="text/javascript">
			window.onload = function() {
				alert('loaded');
			};
		</script>
	</head>
	<body>
		<div id="all">
			<div class="bg">
				<?php if (\$user): ?>
					Welcome
					<?php echo \$user ?>
				<?php endif ?>
				<div id="ct">
					page content
					
					<div class="requests">Your requests:</div>
					
					<?php foreach (\$user->requests as \$request): ?>
						<div class="request"><?php echo \$request ?></div>
					<?php endforeach ?>
					
					<div class="box logo">
						<h1>
							<a href="http://some_page" title="Page">Page</a>
						</h1>
					</div>
				</div>
			</div>
		</div>
		<div id="bottom">
			<h3>Thanks for visit</h3>
		</div>
	</body>
</html>
EOS;

			spec_parse($spec, $template, $expected);
		});
	});
});
