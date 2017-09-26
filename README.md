 This operator allows to clean in a clever way an xhtml field. Its main goal is twofold:
- allows to use the shorten operator and get a valid result
- allows to use the literal class=html and be safe from XSS attacks
Shorten Problem

Let's take the line tpl of article, it displays the title+ introduction. Unfortunately, there isn't a simple way to limit the length of that introduction, and some authors have a rather extensive interpretation of it (bless them, some put the complete article into the introduction). You can go the hard way and try to make them understand that introduction means, err, an introduction, or do it the easy way and truncate the introduction to a length you find normal.

Unfortunately, the shorten operator doesn't work with xml content, as it might cut the text between an opening tag (eg <b> or <div>) and its closing (</b> and </div>) or in the middle of a tag (eg '<a hre'). If you use xhtml+css positionning for your layout (you should), you are going to have some really funcky results. No good.

For instance, if your output text is "Xavier has <b>really</b> simple examples."
{$node.data_map.intro.content.output.output_text|shorten(22)} <a href="...>Read more</a>

Will have "Read more" in bold.

(rem: to make it funnier, you have to add 4 to the length because it starts with <p>+newline )

It can also stop in the middle of a tag "Xavier has <b" and so on.

Moreover, you might want to "clean" the intro, for instance you don't want to display titles (hn) or embeded files in the line view. In other words you need to limit what tags can be in the intro (eg only keep <i> and <b>)
Security problem

As soon as you need to have minimaly complex layout in an article or want to be able to paste html code from elsewhere, you need to allow the html class in the literal

tag, otherwise, any editor can inject any XSS code (javascript attacks...). Ez solution to this security risk has been to disable it by default (settings/content.ini)
[literal]
AvailableClasses[]
# The class 'html' is disabled by default because it gives editors the
# possibility to insert html and javascript code in XML blocks.
# Don't enable the 'html' class unless you really trust all users who has
# privileges to edit objects containing XML blocks.
#AvailableClasses[]=html
This is unfortunately a rather expensive option as you end up overriding templates to allow the html code you want in specific pages instead of just do that from an xml block.
Solution

The Safe HTML library is very good at cleaning the input and get rid of all these security problems.

http://pixel-apes.com/safehtml/
As a positive side effect, it also clean the generated xhtml (for instance missing closing tags), this make it possible to shorten without having problems.
How to use ?

The extension override the default template used by {attribute_view_gui} for the xml fields. You can now safely allows the html class for literals

You have a new maxlength parameter:
{attribute_view attribute=$node.object.data_map.intro maxlength=42}

what it does is to add a xmlwash() operator, you can also use it directly like that:
{$node.data_map.intro.content.output.output_tex|shorten(42)|xmlwash()}
(obvioulsy xmlwash has to be the last one called)

This extension also offers a strip_tags operator (same syntax than the php version)

If you want to keep only the paragraphs, italic and links:
{$node.data_map.intro.content.output.output_text|strip_tags(array('<p>','<i>','<a>'))|shorten(42)|xmlwash()}
Known bugs and limitations

None, but feel free to send me a mail (ez AT sydesy DOT com) if you find one.

As for the limitations, they are mine and I didn't succeed using the svn here, so I used pubsvn ;(
Screenshot
Your mother was right: you need to wash your xml before showing it
Your mother was right: you need to wash your xml before showing it 


