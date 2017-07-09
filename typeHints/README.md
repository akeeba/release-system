# Type hints

Joomla! 3.4 has replaced a lot of non-namespaced Joomla! Platform classes with namespaced Joomla! Framework classes.
The class autoloader has been modified to map the old classes to the new ones, e.g. `JRegistry` to
`Joomla\Registry\Registry`. However, this mapping is not visible to IDEs like phpStorm which can no longer find the
original class, e.g. `JRegistry`, which now both complain about a missing class and don't provide type hinting.
 
This directory has a number of files which provide the same mapping as a set of classes which extend the originals.
These files are not loaded anywhere, they MUST NOT be loaded anywhere and are here for the benefit of IDEs providing
type hints. This is not a complete set. We only add class mappings as we use them.