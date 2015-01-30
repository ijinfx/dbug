dBug
====

A Joomla debugging plugin that dumps/displays the contents of a variable in a colored tabular format. It wraps the Ospinto dBug (http://dbug.ospinto.com) script.


How to use
====
<pre>
dbug($variable);
</pre>
$variable - the vaiable to dump<br/>

Optional<br/>
<pre>
dbug($variable, 12345, 'Some Text Here', true);
</pre>

(int) $nb			- heading number for reference
(string) $title 	- heading text for reference
boolean $bCollapsed	- to collapsed the debug


Features
====

PHP version of ColdFusion’s cfdump.

Outputs colored and structured tabular variable information.

Variable types supported are: Arrays, Classes/Objects, Database and XML Resources.

Ability to force certain types of output. Example: You can force an object variable to be outputted as an array type variable.

Stylesheet can be easily edited.

Table cells can be expanded and collapsed.

It’s FREE!!!
