Editable Page Templates

These files are loaded by professional-redesign-v12.py on deploy.

Sales page templates:
- about.html
- features.html
- gallery.html
- videos.html

Investor shell templates:
- investors-shell.html
- board-shell.html
- financials-shell.html
- press-shell.html

How to edit sales page templates:
1. Update text, headings, links, and section structure in each sales template file.
2. Keep token placeholders unchanged where needed:
   - {GLOBAL_STYLE}
   - {ROOT}
   - {IMG_*}

How to edit investor shell templates:
1. Update hero text, helper notes, and wrapper layout in the shell files.
2. Keep token placeholders unchanged so synced source content can be injected:
   - [[GLOBAL_STYLE]]
   - [[ROOT]]
   - [[IR_KICKER]]
   - [[IR_TITLE]]
   - [[IR_SUBTITLE]]
   - [[IR_HERO_IMAGE]]
   - [[IR_CONTENT]]

Deploy command:
- python professional-redesign-v12.py

Available image tokens for sales templates:
- {IMG_logo}
- {IMG_hero}
- {IMG_hero2}
- {IMG_hero3}
- {IMG_hero4}
- {IMG_grove}
- {IMG_grove2}
- {IMG_sea}
- {IMG_contact}
- {IMG_park1}
- {IMG_park2}
- {IMG_park3}
- {IMG_park4}
- {IMG_marg}
- {IMG_ritz}
- {IMG_niagara}
- {IMG_marriott}
- {IMG_partner1}
- {IMG_partner2}
- {IMG_boardhero}
- {IMG_financehero}
- {IMG_presshero}
- {IMG_lifestyle1}
- {IMG_lifestyle2}
- {IMG_lifestyle3}
- {IMG_gallery_water1}
- {IMG_gallery_water2}
- {IMG_gallery_water3}
- {IMG_gallery_water4}
- {IMG_gallery_water5}
- {IMG_gallery_water6}
- {IMG_gallery_water7}
- {IMG_gallery_detail1}
- {IMG_gallery_detail2}
- {IMG_gallery_detail3}
