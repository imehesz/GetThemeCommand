USAGE
=====
  `gettheme [--options] theme_id[ theme_id2 theme_id3 ... etc]`

DESCRIPTION
===========
use this command-line tool to download and install theme(s) 
from the Yii Themes site for your Yii powered application.
http://yiithemes.mehesz.net

OPTIONS
=======
--tp, --themespath

set the themes folder where the themes will be downloaded 
and installed (default is ... - use full path! )

--ni, --noinstall

script will only download the theme(s) and will not install 
(default is `false`, meaning it automatically installs the theme(s))

EXAMPLES
========
To download and install themes just execute
`./yiic gettheme 1 2 3`

To only download themes
`./yiic gettheme --ni 1 2 3`

