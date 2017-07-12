# Requirements #

PHP Version: >= 5.5.*

PHP Extensions:

http://php.net/manual/en/book.tidy.php

http://php.net/manual/en/class.domdocument.php

http://php.net/manual/en/book.curl.php

http://php.net/manual/en/book.mbstring.php

# PHP Extensions Install #

sudo apt-get -y install php5-tidy php5-curl

# Usage Example #

php spider.php http://www.reporo.com/

# Notes #

Supports HTTP authentication. I.e.: http://username:password@domain.tld/

Does not follow redirects.

Does not recursively follow iframes (goes 1 level deep).

A security risk, this script ignores SSL certificate checks.

URLs without a trailing slash are treated as paths to a script, as such the last part after
/ is removed when building relative paths.
 
Certain resources, such as JavaScript appended tags are ignored.

Dues to ignoring redirects and JS appended tags, items such as Facebook Like buttons are ignored.

Certain pages may return dummy content if an unknown browser is used.  
The (optional) user agent may be set using the Util\Curl setUserAgent method.

CSS file external resources are ignored.