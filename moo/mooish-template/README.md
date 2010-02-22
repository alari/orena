Mooish Template
===============
A template for building JS libraries structured like [Mootools][1], works
great for building code on top of Mootools (plugins/classes/extensions etc).
I use this to store and maintain all of my reusable JS that I use in my 
various projects. It is essentially a directory of code with each component
in its own file, a file defining dependencies, a builder ruby script and two 
test frameworks. Included is some sample code w/ tests. It maintains pointers
in various places to other JS library directories (with their own `scripts.json`
file) so that it can include all the dependencies at build time. Read the
"Initial Setup" section for help on this. 

The most important part of this organization scheme is that all of your js code
lives in one place, is tested in one place and is pushed/pulled from one place. 
You don't have to bother updating a mootools file in all of your projects 
when a new version comes out. You just update your *one* mootools or repo 
and use the builder to make new lib js files for your projects. Better still, all
of the tests in this template pull the code from your mootools repo 
dynamically before each test is run - there really isn't a stagnant copy of 
mootools or other js library lying around anywhere.


Initial Setup
-------------

  This template works best if you have a mootools-core directory/repo
  on your disk somewhere. I keep mine at the same level as this directory as
  its own git repo so I can have all the remotes setup the way I want. This 
  means I can switch the branch of my mootools-core repo to a different version
  etc. and build `lib.js` files with that version of moo. Very convenient. If you
  want your mootools-core and other libraries in a different place just search
  for the string "../mootools-core" in this project and correct it (and the other
  library paths) to your specifications. You will probably want to look at: 
  `build.yml`, `config.js`, `index.html` and `suite.html`. 
  
  After that follow the directions below to add you code to Source/, Tests/ and
  Specs/


What's in the box?
------------------
  You may have noticed there are some files in this directory. Let me explain:

### builder.rb

  There is a customized bulder.rb script (based on the one included in moo-core)
  that allows for multiple pointers to `scripts.json` files. With this you can
  build a customized js file that includes classes from any library (with a 
  `scripts.json` file), including your own source code. For example:
  
    $> ruby build.rb -l "Core JsonP MyRadClassIsTight"
    
  Notice that all three of the supplied dependencies are from different
  libraries (Mootools-core, Clientcide and fake respectively). Since all
  three have `scripts.json` files that link each other up, the build will produce
  a file with those three classes concatenated along with all of their 
  dependencies.
  
  This really comes in handy for building custom Javascript lib files for your
  projects. For this reason there is a projects.yml file you can use to store
  these project class lists. 
  
  For example, my site www.iancollins.me uses classes from Mootools, 
  Clientcide and Brawndo. In my projects.yml file in the modules section I have:
    
    Request.JSONP Date Date.Extras JustTheTip Number.BrawndoExtras Function.BrawndoExtras 
    Element.BrawndoScrolling Element.BrawndoExtras Element.BrawndoImage 
    Element.BrawndoStyles Element.BrawndoDisplaying params String.BrawndoExtras 
    Array.BrawndoExtras dbug TwitterHelpers RandomGlobals DomReady 
    CSSTransitions.Tween
    
  Now to build all the necessary lib js I simple do: 
  
    ruby build.rb -p 3n
    
### builder.yml

  This is a YAML defining paths to all of the project's `scripts.json` files that
  you want depend on for this project. 
  
### projects.yml

  This is a YAML defining the classes (modules) needed for each of your projects,
  as well as the file path for the output of the build process. 
    
### Source/

  This is where your code lives. There is a dummy directory and file as an example,
  these can be deleted. Each class or set of extensions should be in its own file,
  in a directory that makes sense for what it does. 
  
### Source/scripts.json

  This is a JSON representation of your code inside Source - __you__ keep this file
  up-to-date. It has an accurate representation of the code provided in /Source, 
  with dependencies from Mootools-core.
  
### Specs/

  This is a basic Screw.Unit setup for your specs. Delete this is you'd rather
  use JSSpec or some other crazy framework I don't know about. For help on adding
  tests here look at the first part of "3." under the header "Adding your code". 
  
### Tests/

  This is a basic [Mootool Unit Test Framework][3] setup for your interactive tests. 
  For help on adding tests here look at the second part of "3." under the header 
  "Adding your code".
  
### Demo/

  A basic example of a demo html/js combo. The html file uses the Mootools builder
  to import the code to be demoed (along with all of mootools) and has some sample
  elements to test on. The js file just sets up a simple test case of the component
  being demoed. 
  
  
Adding your code
----------------

  Got some sweet code to add to your project? Follow these simple steps to glory:

1. Add the class/class.extension in its own file in the Source directory. 
   If it doesn't fit into one of the directories there, create a new one. 
2. Add your new filename to `scripts.json`. The first level of the hash there
   is for the directories under Source. The 2nd level is for the files in
   those directories. Make sure to add a desc(ription) and dep(endencie)s.
   The dependencies can be anything found in a `scripts.json` file you pointed
   to in build.yml. 
3. If this particular code doesn't do anything visual, add your tests to 
   `Specs/sample_specs.js` in its own describe block. You will also have 
   to add the directory/filename to the custom Builder call at the top of 
   Specs/suite.html (around line 20) so that the test suite will know to pull
   in your code before it runs. You may also have to edit src and 
   Builder.root locations to point at your own Mootools-core repo. 

   Otherwise, you'll need to add a test to the Unit Test Framework. Follow
   the same steps for adding a director/file from 1. but do it in 
   Tests/UserTests and add two files: `ClassName.testname.js` and 
   `ClassName.testname.html`. Now add your ClassName & testname to 
   `UserTests/test.json` so they'll show up in the list. Oh also maybe write
   test code and html in those two files (look at the others for an example).
  
  
Running your tests
------------------

  This template comes with two test suites: Screwunit for specs and Mootools Unit 
  Test Framework for the more interactive tests. The specs cover all of the 
  components that can be tested non-visually. The unit tests cover everything that 
  needs a human to judge the result. 
  
  For the interactive tests you should have Apache/Nginx or some other webserver 
  serving the files. Just point your browser at the Tests directory. For the 
  specs you don't need the webserver - just point your browser at Specs/suite.html.
  You may also need to edit `/Tests/config.js` to point to the right library source
  directories. The first object being passed in to the UnitTester constructor
  is what you should change. 
  
  IMPORTANT: The UnitTester library is now a submodule so you need to run: 
  
    git submodule init && git submodule update
    
  for it to work. You should also read the "Configuration" section in 
  Tests/UnitTester after you get the submodule. It will tell you what files to
  copy up into your Tests directory for everything to work right. 
  
  
Building a custom lib js file
-----------------------------

  First, edit the build.yml and projects.yml files to suite your needs. 
  Add/remove items from the dependency_paths list to point to all of the 
  scripts.json files you care about. In my setup I point to mootools-more, 
  mootools-core, clientcide and of course, Brawndo™ (the Source directory here). 

  Now, in the root directory of the project run:

    ruby build.rb -l "Class1 Class2 Class3" [-o output_path]

  List as many filenames (class names, usually) as you want, separated by spaces. 
  If you aren't sure of what you can put in this list, look at any scripts.json
  file (there is one in the Source directory here) - anything at the 2nd depth
  in the hash is fair game. Examples: Array, InvisibleDimensions, Request.JSONP

  If you have a projects.yml file with some project's module list in it you can
  do the following:

    ruby build.rb -p project_name

  The output for the file in this case is specified along with the project definition
  in projects.yml.
  
Exporting plugins for use on the Forge
--------------------------------------

  _Here, a plugin is a series of scripts/classes from either this repo or one 
  pointed to from build.yml and a package.yml file_

  First, edit the build.yml and plugins.yml files to suite your needs.
  For help on building these look at build.yml.example and plugins.yml.example.

  To build a directory ready for pushing to the Forge, you have two options:

  1. _ruby export.rb -p "JustTheTip"_ builds the plugin "JustTheTip" found in
     your plugins.yml file. The package.yml file will include all of the
     requires/references as well as the metadata found for that plugin in 
     plugins.yml. All scripts for this plugin listed in plugins.yml are copied 
     into the output directory.
  2. _ruby export.rb -l "JustTheTip JustTheTip.Ajax"_ builds the plugin 
     "JustTheTip" from scratch. The package.yml file will include all of the
     requires/references __only__. Both scripts necessary are copied into the 
     output directory.

For any extra help just do _ruby export.rb -h_.
  
[1]: http://mootools.net/
[2]: http://www.clientcide.com  
[3]: http://www.clientcide.com/TestFramework/readme.html