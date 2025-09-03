<?php
/**
 * Audit Brand-Specific Gear References
 * Generates a comprehensive inventory of all brand mentions in the codebase
 */

$projectRoot = dirname(__DIR__);
$brands = [
    'Zpacks', 'Big Agnes', 'Nemo', 'Therm-a-Rest', 'Thermarest', 'MSR', 'Sawyer', 
    'Katadyn', 'Gossamer Gear', 'GossamerGear', 'Hyperlite', 'HMG', 'Osprey', 'REI', 
    'Enlightened Equipment', 'EE', 'MLD', 'Mountain Laurel', 'Durston', 'SMD', 
    'Six Moon Designs', 'Black Diamond', 'BD', 'Petzl', 'Jetboil', 'Toaks', 'TOAKS',
    'Snow Peak', 'Garmin', 'Patagonia', 'Arc\'teryx', 'Arcteryx', 'Mountain Hardwear',
    'Outdoor Research', 'OR', 'Salomon', 'Altra', 'Hoka', 'Merrell', 'Solomon',
    'Smartwool', 'Darn Tough', 'Injinji', 'Sea to Summit', 'Platypus', 'CamelBak',
    'Nalgene', 'SmartWater', 'Smart Water', 'Anker', 'Nitecore', 'Frogg Toggs',
    'Montbell', 'Feathered Friends', 'Western Mountaineering', 'WM', 'Katabatic',
    'ULA', 'Granite Gear', 'Gregory', 'Deuter', 'Kelty', 'Tarp Tent', 'TarpTent',
    'Hilleberg', 'Naturehike', 'Decathlon', 'Forclaz', 'Quechua', 'Columbia',
    'North Face', 'The North Face', 'TNF', 'Marmot', 'Rab', 'Mammut', 'Fjallraven',
    'prAna', 'Prana', 'Eddie Bauer', 'LL Bean', 'Exped', 'Klymit', 'Big Sky',
    'Bearikade', 'BearVault', 'Ursack', 'Garcia', 'BRS', 'Soto', 'Primus', 'Optimus',
    'Trangia', 'Esbit', 'Evernew', 'GSI', 'IMUSA', 'Stanley', 'Hydro Flask',
    'Lifestraw', 'Aquamira', 'Aquatabs', 'Iodine', 'SteriPen', 'GRAYL', 'Grayl',
    'Cnoc', 'CNOC', 'Hydrapak', 'HydraPak', 'Gatorade', 'Powerade', 'Nuun',
    'Tailwind', 'Skratch', 'GU', 'Cliff', 'Clif', 'Kind', 'RX', 'RXBAR', 'Larabar',
    'ProBar', 'Epic', 'Country Archer', 'Jack Links', 'Jack Link\'s', 'Starbucks',
    'Via', 'Mount Hagen', 'Alpine Start', 'Trail Butter', 'Justin\'s', 'Justins',
    'Honey Stinger', 'Gatorz', 'Julbo', 'Oakley', 'Goodr', 'Tifosi', 'Native',
    'Buff', 'Sunday Afternoons', 'Tilley', 'Outdoor Products', 'Coghlan', 'Coghlans',
    'Coleman', 'Eno', 'ENO', 'Eagles Nest', 'Kammok', 'Hennessy', 'Warbonnet',
    'Dream Hammock', 'Dutchware', 'Hummingbird', 'Superior', 'Helinox', 'Therm-a-Rest',
    'Thermarest', 'Crazy Creek', 'Alite', 'Rumpl', 'Kammok', 'Puffy', 'Zenbivy',
    'Nemo', 'Sierra Designs', 'Stoic', 'Cotopaxi', 'Hyperlite Mountain Gear',
    'Zimmerbuilt', 'MLD', 'Palante', 'Superior Wilderness', 'Nashville Pack',
    'Atom Packs', 'Northern Ultralight', 'Seek Outside', 'Stone Glacier',
    'Mystery Ranch', 'Kifaru', 'Hill People Gear', 'Klattermusen', 'Haglofs',
    'Vaude', 'Lowe Alpine', 'Macpac', 'Kathmandu', 'Berghaus', 'Vango', 'Eureka',
    'ALPS', 'Victorinox', 'Leatherman', 'Gerber', 'SOG', 'Benchmade', 'Spyderco',
    'Mora', 'Morakniv', 'Opinel', 'CRKT', 'Buck', 'Case', 'Kershaw', 'Zero Tolerance',
    'Fenix', 'Olight', 'Streamlight', 'Surefire', 'Maglite', 'Princeton Tec',
    'Silva', 'Suunto', 'Brunton', 'Casio', 'Timex', 'Coros', 'Polar', 'Wahoo',
    'Fitbit', 'Apple', 'Samsung', 'Motorola', 'InReach', 'Spot', 'ACR', 'PLB',
    'Luci', 'MPOWERD', 'Goal Zero', 'BioLite', 'Jackery', 'EcoFlow', 'Bluetti',
    'Westcott', 'Fiskars', 'X-acto', 'Olfa', 'Exacto', 'Milwaukee', 'DeWalt',
    'Makita', 'Bosch', 'Ryobi', 'Craftsman', 'Husky', 'Kobalt', 'Ridgid',
    'Tenacious Tape', 'Gear Aid', 'McNett', 'Gorilla', 'Duct', 'Duck', '3M',
    'Scotch', 'Command', 'Velcro', 'YKK', 'Coats', 'Gutermann', 'Madeira',
    'Dr. Bronner', 'Dr. Bronners', 'Bronner', 'Camp Suds', 'Wilderness Wash',
    'Purell', 'Germ-X', 'Wet Ones', 'Dude Wipes', 'Kleenex', 'Charmin', 'Cottonelle',
    'Scott', 'Angel Soft', 'Quilted Northern', 'Bounty', 'Viva', 'Brawny',
    'Band-Aid', 'Band Aid', 'Curad', 'Nexcare', 'Johnson', 'J&J', 'Tylenol',
    'Advil', 'Motrin', 'Aleve', 'Aspirin', 'Bayer', 'Excedrin', 'Benadryl',
    'Zyrtec', 'Claritin', 'Allegra', 'Sudafed', 'Mucinex', 'Dayquil', 'Nyquil',
    'Robitussin', 'Halls', 'Ricola', 'Fisherman', 'Vicks', 'Neosporin', 'Bacitracin',
    'Polysporin', 'Aquaphor', 'Vaseline', 'Eucerin', 'Cetaphil', 'CeraVe',
    'Aveeno', 'Lubriderm', 'Jergens', 'Nivea', 'Dove', 'Olay', 'Old Spice',
    'Degree', 'Secret', 'Mitchum', 'Dove', 'Irish Spring', 'Dial', 'Ivory',
    'Zest', 'Safeguard', 'Lifebuoy', 'Lava', 'Borax', 'Fels Naptha', 'Zote',
    'Castile', 'Kirk', 'Pears', 'Lux', 'Camay', 'Palmolive', 'Colgate', 'Crest',
    'Sensodyne', 'Aquafresh', 'Aim', 'Close-Up', 'Pepsodent', 'Mentadent',
    'Rembrandt', 'Tom\'s', 'Toms', 'Jason', 'Nature\'s Gate', 'Kiss My Face',
    'Alba', 'Badger', 'Burt\'s Bees', 'Burts Bees', 'Carmex', 'Chapstick',
    'Blistex', 'EOS', 'Lip Smacker', 'Maybelline', 'Revlon', 'Covergirl',
    'L\'Oreal', 'Loreal', 'Neutrogena', 'Olay', 'Clean & Clear', 'Clean and Clear',
    'Clearasil', 'Stridex', 'Oxy', 'Proactiv', 'Differin', 'Retin-A', 'Retin A',
    'Accutane', 'Coppertone', 'Banana Boat', 'Hawaiian Tropic', 'Bullfrog',
    'Blue Lizard', 'Australian Gold', 'Sun Bum', 'Coola', 'Supergoop', 'La Roche',
    'Vichy', 'Avene', 'Bioderma', 'Eucerin', 'Helioplex', 'Anthelios',
    'Think Sport', 'All Good', 'Raw Elements', 'Goddess Garden', 'Zinka',
    'Quaker', 'Kellogg', 'General Mills', 'Post', 'Nature Valley', 'Nutri-Grain',
    'Nutrigrain', 'Belvita', 'Fiber One', 'Special K', 'Cheerios', 'Lucky Charms',
    'Frosted Flakes', 'Corn Flakes', 'Rice Krispies', 'Fruit Loops', 'Cocoa Puffs',
    'Trix', 'Captain Crunch', 'Life', 'Chex', 'Kix', 'Wheaties', 'Total',
    'Raisin Bran', 'Grape Nuts', 'Shredded Wheat', 'Honey Nut', 'Cinnamon Toast',
    'Reese\'s', 'Reeses', 'Hershey', 'Snickers', 'Milky Way', 'Twix', 'Kit Kat',
    'Butterfinger', 'Baby Ruth', 'Pay Day', 'Almond Joy', 'Mounds', '100 Grand',
    'Nestle', 'Mars', 'Cadbury', 'Godiva', 'Ghirardelli', 'Lindt', 'Ferrero',
    'Toblerone', 'Ritter Sport', 'Milka', 'Kinder', 'Dove', 'Galaxy', 'Aero',
    'Yorkie', 'Bounty', 'Maltesers', 'M&M', 'M&Ms', 'Skittles', 'Starburst',
    'Mike and Ike', 'Hot Tamales', 'Red Vines', 'Twizzlers', 'Sour Patch',
    'Swedish Fish', 'Haribo', 'Albanese', 'Black Forest', 'Welch\'s', 'Welchs',
    'Annie\'s', 'Annies', 'Goldfish', 'Cheez-It', 'Cheezit', 'Cheese Nips',
    'Ritz', 'Club', 'Townhouse', 'Triscuit', 'Wheat Thins', 'Chicken in a Biskit',
    'Better Cheddars', 'Cheese-Its', 'Cheetos', 'Doritos', 'Fritos', 'Lays',
    'Lay\'s', 'Ruffles', 'Pringles', 'Kettle', 'Cape Cod', 'Utz', 'Wise',
    'Herr\'s', 'Herrs', 'Zapp\'s', 'Zapps', 'Terra', 'Popchips', 'Baked Lays',
    'Sun Chips', 'Tostitos', 'Mission', 'Old El Paso', 'Ortega', 'Chi-Chi\'s',
    'Chi Chis', 'Pace', 'Herdez', 'Rotel', 'Goya', 'La Preferida', 'Rosarita',
    'Refried', 'Bush\'s', 'Bushs', 'Heinz', 'Hunt\'s', 'Hunts', 'Del Monte',
    'Green Giant', 'Libby\'s', 'Libbys', 'Progresso', 'Campbell\'s', 'Campbells',
    'Healthy Choice', 'Marie Callender', 'Stouffer\'s', 'Stouffers', 'Lean Cuisine',
    'Smart Ones', 'Weight Watchers', 'Amy\'s', 'Amys', 'Kashi', 'Morningstar',
    'Boca', 'Gardein', 'Beyond', 'Impossible', 'Lightlife', 'Tofurky', 'Field Roast',
    'Daiya', 'Violife', 'Follow Your Heart', 'Miyoko\'s', 'Miyokos', 'Kite Hill',
    'Silk', 'Almond Breeze', 'Califia', 'Oatly', 'Ripple', 'Planet Oat',
    'Elmhurst', 'Milkadamia', 'Good Karma', 'Westsoy', 'Eden', 'Pacific',
    'Imagine', 'Kitchen Basics', 'Swanson', 'College Inn', 'Better Than Bouillon',
    'Knorr', 'Maggi', 'Lipton', 'McCormick', 'Lawry\'s', 'Lawrys', 'Mrs. Dash',
    'Mrs Dash', 'Old Bay', 'Tony Chachere', 'Zatarains', 'Zatarain\'s', 'Slap Ya Mama',
    'Emeril\'s', 'Emerils', 'Paul Prudhomme', 'Tabasco', 'Crystal', 'Frank\'s',
    'Franks', 'RedHot', 'Texas Pete', 'Cholula', 'Tapatio', 'Valentina', 'Sriracha',
    'Huy Fong', 'Sambal', 'Gochujang', 'Harissa', 'Chimichurri', 'Pesto',
    'Alfredo', 'Marinara', 'Ragu', 'Prego', 'Bertolli', 'Newman\'s Own', 'Newmans Own',
    'Classico', 'Rao\'s', 'Raos', 'Barilla', 'Ronzoni', 'Mueller\'s', 'Muellers',
    'Prince', 'San Giorgio', 'De Cecco', 'Colavita', 'Garofalo', 'Rummo',
    'Banza', 'Barilla', 'Dreamfields', 'Hodgson Mill', 'Bob\'s Red Mill',
    'Bobs Red Mill', 'King Arthur', 'Pillsbury', 'Gold Medal', 'Betty Crocker',
    'Duncan Hines', 'Jiffy', 'Bisquick', 'Krusteaz', 'Kodiak', 'Birch Benders',
    'Simple Mills', 'Cup4Cup', 'Pamela\'s', 'Pamelas', 'Glutino', 'Schar',
    'Udi\'s', 'Udis', 'Canyon Bakehouse', 'Franz', 'Dave\'s', 'Daves', 'Sara Lee',
    'Pepperidge Farm', 'Arnold', 'Brownberry', 'Oroweat', 'Nature\'s Own',
    'Natures Own', 'Wonder', 'Sunbeam', 'Bunny', 'Martin\'s', 'Martins',
    'Thomas\'', 'Thomas', 'Bimbo', 'Entenmann\'s', 'Entenmanns', 'Hostess',
    'Little Debbie', 'Drake\'s', 'Drakes', 'Tastykake', 'Lance', 'Nabisco',
    'Oreo', 'Chips Ahoy', 'Nutter Butter', 'Nilla', 'Teddy Grahams', 'Honey Maid',
    'Graham', 'Keebler', 'Famous Amos', 'Pepperidge Farm', 'Milano', 'Goldfish',
    'Archway', 'Mother\'s', 'Mothers', 'Murray', 'Voortman', 'Walkers', 'McVities',
    'McVitie\'s', 'Lotus', 'Biscoff', 'Stroopwafel', 'Leibniz', 'Bahlsen',
    'LU', 'Petit Beurre', 'Maria', 'Digestive', 'Rich Tea', 'Bourbon', 'Custard Cream',
    'Jammy Dodger', 'Jaffa', 'Hobnob', 'Penguin', 'Tim Tam', 'Anzac', 'Arnotts',
    'Arnott\'s', 'Britannia', 'Parle', 'Hide & Seek', 'Hide and Seek', 'Monaco',
    'Krackjack', 'Good Day', 'Marie Gold', 'Tiger', 'Khong Guan', 'Julie\'s',
    'Julies', 'Munchy\'s', 'Munchys', 'Rebisco', 'Sky Flakes', 'Fita', 'Cream-O',
    'Nissin', 'Monde', 'Oishi', 'Jack \'n Jill', 'Jack n Jill', 'Universal Robina',
    'Ricoa', 'Fibisco', 'Dewey\'s', 'Deweys', 'Mary\'s Gone Crackers',
    'Marys Gone Crackers', 'Simple Mills', 'Hu', 'Siete', 'Hippeas', 'Lesser Evil',
    'Boom Chicka Pop', 'Skinny Pop', 'Angie\'s', 'Angies', 'Pirate\'s Booty',
    'Pirates Booty', 'Veggie Straws', 'Terra', 'Beanitos', 'Beanfields',
    'Harvest Snaps', 'Snapea', 'Bare', 'Rhythm', 'Dang', 'Made Good', 'MadeGood',
    'That\'s It', 'Thats It', 'Pure', 'Peeled', 'Solely', 'Barnana', 'Mavuno',
    'Natierra', 'Made in Nature', 'Navitas', 'Terrasoul', 'Viva Naturals',
    'Anthony\'s', 'Anthonys', 'Healthworks', 'Jiva', 'Organic', 'Nutiva',
    'Spectrum', 'La Tourangelle', 'Chosen', 'Primal Kitchen', 'Bragg', 'Braggs',
    'Pompeian', 'Filippo Berio', 'Bertolli', 'Colavita', 'Star', 'Crisco',
    'Wesson', 'Mazola', 'Pam', 'Baker\'s Joy', 'Bakers Joy', 'Wilton', 'Ateco',
    'Nordic Ware', 'USA Pan', 'Chicago Metallic', 'Calphalon', 'All-Clad',
    'All Clad', 'Cuisinart', 'KitchenAid', 'Kitchen Aid', 'Oster', 'Hamilton Beach',
    'Black & Decker', 'Black and Decker', 'Proctor Silex', 'Sunbeam', 'Rival',
    'Crock-Pot', 'Crock Pot', 'Instant Pot', 'Ninja', 'Vitamix', 'Blendtec',
    'NutriBullet', 'Magic Bullet', 'Oster', 'Breville', 'Cuisinart', 'Waring',
    'Immersion', 'Braun', 'Bamix', 'KitchenAid', 'Kitchen Aid', 'Hobart',
    'Bosch', 'Ankarsrum', 'Kenwood', 'Smeg', 'Dualit', 'Magimix', 'Robot Coupe',
    'Hobart', 'Globe', 'Berkel', 'Bizerba', 'Vollrath', 'Cambro', 'Carlisle',
    'Rubbermaid', 'Sterilite', 'OXO', 'Joseph Joseph', 'Simplehuman', 'Umbra',
    'Yamazaki', 'Muji', 'Daiso', 'Lock & Lock', 'Lock and Lock', 'Snapware',
    'Pyrex', 'Anchor Hocking', 'Corelle', 'Corningware', 'Fiesta', 'Homer Laughlin',
    'Lenox', 'Wedgwood', 'Royal Doulton', 'Spode', 'Portmeirion', 'Mikasa',
    'Noritake', 'Villeroy & Boch', 'Villeroy and Boch', 'Rosenthal', 'Hutschenreuther',
    'Meissen', 'Royal Copenhagen', 'Arabia', 'Iittala', 'Marimekko', 'Hackman',
    'Fiskars', 'Rig-Tig', 'Rig Tig', 'Eva Solo', 'Stelton', 'Georg Jensen',
    'Menu', 'Normann Copenhagen', 'Hay', 'Muuto', 'Skagerak', 'Fritz Hansen',
    'Vipp', 'Vitra', 'Alessi', 'Kartell', 'Magis', 'Driade', 'Zanotta',
    'Cassina', 'B&B Italia', 'B&B', 'Poltrona Frau', 'Minotti', 'Flexform',
    'Moroso', 'Molteni', 'Boffi', 'Modulnova', 'Valcucine', 'Ernestomeda',
    'Snaidero', 'Scavolini', 'Arclinea', 'Effeti', 'Pedini', 'Alno', 'Poggenpohl',
    'SieMatic', 'Bulthaup', 'Leicht', 'Nobilia', 'Hacker', 'Nolte', 'Schuller',
    'IKEA', 'West Elm', 'CB2', 'Crate & Barrel', 'Crate and Barrel', 'Pottery Barn',
    'Williams Sonoma', 'Restoration Hardware', 'RH', 'Room & Board', 'Room and Board',
    'Design Within Reach', 'DWR', 'Herman Miller', 'Steelcase', 'Knoll', 'Haworth',
    'Humanscale', 'Hon', 'Allsteel', 'Kimball', 'Teknion', 'Global', 'OFS',
    'KI', 'Okamura', 'Kokuyo', 'Itoki', 'Uchida', 'Plus', 'Lixil', 'Takara Standard',
    'Cleanup', 'Sunwave', 'Toclas', 'Noritz', 'Rinnai', 'Paloma', 'Corona',
    'Chofu', 'Mitsubishi', 'Daikin', 'Panasonic', 'Hitachi', 'Toshiba', 'Sharp',
    'Sony', 'Canon', 'Nikon', 'Fujifilm', 'Olympus', 'Pentax', 'Ricoh', 'Sigma',
    'Tamron', 'Tokina', 'Zeiss', 'Leica', 'Hasselblad', 'Phase One', 'Mamiya',
    'Profoto', 'Broncolor', 'Elinchrom', 'Hensel', 'Bowens', 'Paul C. Buff',
    'Paul C Buff', 'AlienBees', 'Einstein', 'White Lightning', 'Godox', 'Yongnuo',
    'Nissin', 'Metz', 'Westcott', 'Lastolite', 'Photoflex', 'Chimera', 'Lighttools',
    'Matthews', 'Avenger', 'Manfrotto', 'Gitzo', 'Really Right Stuff', 'RRS',
    'Arca-Swiss', 'Arca Swiss', 'Kirk', 'Wimberley', 'Jobu', 'Induro', 'Benro',
    'Sirui', 'Vanguard', 'Slik', 'Velbon', 'Davis & Sanford', 'Davis and Sanford',
    'Dolica', 'MeFOTO', 'ProMaster', 'Pro Master', 'Oben', 'Three Legged Thing',
    'Feisol', 'FLM', 'Novoflex', 'Cullmann', 'Berlebach', 'Wood', 'Peak Design',
    'Think Tank', 'ThinkTank', 'Lowepro', 'Tamrac', 'Domke', 'Billingham',
    'ONA', 'Langly', 'Wandrd', 'WANDRD', 'Shimoda', 'F-Stop', 'F Stop', 'Mindshift',
    'Mind Shift', 'Vanguard', 'Tenba', 'Case Logic', 'Pelican', 'Storm', 'SKB',
    'Nanuk', 'HPRC', 'Explorer', 'Seahorse', 'Monoprice', 'Apache', 'Plano',
    'Flambeau', 'MTM', 'Plano', 'Cabela\'s', 'Cabelas', 'Bass Pro', 'Sportsman\'s',
    'Sportsmans', 'Dick\'s', 'Dicks', 'Academy', 'Dunham\'s', 'Dunhams', 'Big 5',
    'Big Five', 'Modell\'s', 'Modells', 'Sports Authority', 'Sport Chalet',
    'Eastern Mountain Sports', 'EMS', 'Gander', 'Field & Stream', 'Field and Stream',
    'Orvis', 'Simms', 'Sage', 'Redington', 'Echo', 'TFO', 'St. Croix', 'St Croix',
    'Loomis', 'Lamiglas', 'Fenwick', 'Ugly Stik', 'Shakespeare', 'Abu Garcia',
    'Penn', 'Shimano', 'Daiwa', 'Okuma', 'Pflueger', 'Quantum', 'Zebco',
    'Mitchell', 'Van Staal', 'Tibor', 'Abel', 'Nautilus', 'Ross', 'Lamson',
    'Waterworks', 'Hardy', 'Greys', 'Loop', 'Danielsson', 'Bogdan', 'Vom Hofe',
    'Pfleuger', 'Islander', 'Accurate', 'Avet', 'Timar'
];

$inventory = [];
$filesToScan = [
    'assets/js/pack-manager-pro.js',
    'assets/js/pack-manager-pro-enhanced.js',
    'assets/js/pack-builder.js',
    'assets/js/pack-builder-enhanced.js',
    'assets/js/backpack-manager.js',
    'storage/sqlite/seeds/default_gear.json',
    'storage/json/gear.json',
    'app/helpers/seed_default_gear.php',
    'api/routes/gear.php',
    'api/routes/gear_v2.php',
    'backend/api/v1/gear.php',
    'backend/create_gear_tables.php',
    'app/helpers/migrations/2025_gear_library.php'
];

// Scan each file
foreach ($filesToScan as $file) {
    $fullPath = $projectRoot . '/' . $file;
    if (!file_exists($fullPath)) {
        continue;
    }
    
    $content = file_get_contents($fullPath);
    $lines = explode("\n", $content);
    
    foreach ($lines as $lineNum => $line) {
        $lineNumber = $lineNum + 1;
        foreach ($brands as $brand) {
            if (stripos($line, $brand) !== false) {
                // Extract context (surrounding 50 chars)
                $pos = stripos($line, $brand);
                $start = max(0, $pos - 30);
                $length = min(strlen($line) - $start, 100);
                $context = substr($line, $start, $length);
                
                if (!isset($inventory[$file])) {
                    $inventory[$file] = [];
                }
                
                $inventory[$file][] = [
                    'line' => $lineNumber,
                    'brand' => $brand,
                    'context' => trim($context),
                    'full_line' => trim($line)
                ];
            }
        }
    }
}

// Sort inventory by file
ksort($inventory);

// Create report
$report = [
    'generated_at' => date('Y-m-d H:i:s'),
    'total_files_with_brands' => count($inventory),
    'total_brand_mentions' => array_sum(array_map('count', $inventory)),
    'files' => $inventory,
    'unique_brands_found' => []
];

// Collect unique brands found
$uniqueBrands = [];
foreach ($inventory as $file => $mentions) {
    foreach ($mentions as $mention) {
        $uniqueBrands[strtolower($mention['brand'])] = $mention['brand'];
    }
}
$report['unique_brands_found'] = array_values($uniqueBrands);

// Save report
$reportPath = $projectRoot . '/test/reports/gear_brand_inventory.json';
if (!is_dir(dirname($reportPath))) {
    mkdir(dirname($reportPath), 0755, true);
}
file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// Console output
echo "Brand Gear Audit Report\n";
echo "=======================\n";
echo "Generated: " . $report['generated_at'] . "\n";
echo "Total files with brands: " . $report['total_files_with_brands'] . "\n";
echo "Total brand mentions: " . $report['total_brand_mentions'] . "\n";
echo "Unique brands found: " . count($report['unique_brands_found']) . "\n\n";

foreach ($inventory as $file => $mentions) {
    echo "\n📁 $file (" . count($mentions) . " mentions)\n";
    echo str_repeat('-', strlen($file) + 15) . "\n";
    
    foreach ($mentions as $mention) {
        echo "  Line {$mention['line']}: {$mention['brand']}\n";
        echo "    Context: {$mention['context']}\n";
    }
}

echo "\n✅ Report saved to: test/reports/gear_brand_inventory.json\n";
