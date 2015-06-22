# mahara-openbadgedisplayer
Open Badges displayer plugin for Mahara

Open Badge is a concept developed by the Mozilla Foundation. Read more here:

http://openbadges.org/


## Requirements

Mahara 1.8 or newer.


## Installation

1. Copy the openbadgedisplayer/ folder to htdocs/blocktype/

2. Log in as admin, go to Administration => Extensions,
   click install link for this plugin under blocktype column.

3. Add Backpack sources to your htdocs/config.php file:


    // Pull badges from Mozilla Backpack and openbadgepassport.com
    $cfg->openbadgedisplayer_source = array(
        'backpack' => 'https://backpack.openbadges.org/',
        'passport' => 'https://openbadgepassport.com/'
    );


## Usage

In order to display badges in a portfolio or profile page, two things are needed:

1. The User must create public badge collections in their Mozilla backpack.

2. The email address used with backpack must be one of the verified addresses in Mahara profile.

After that, badge collections should become selectable in the block configuration popup.


Copyright 2013-2014 Discendum Oy http://www.discendum.com
