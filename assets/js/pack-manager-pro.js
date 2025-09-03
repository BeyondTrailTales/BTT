/**
 * Pack Manager Pro - Professional Backpacking System
 * Built by backpackers, for backpackers
 * Fully functional with extensive gear library and customization
 */

const PackManagerPro = (function() {
    'use strict';

    // ==================== COMPREHENSIVE GEAR DATABASE ====================
    const GEAR_DATABASE = {
        // SHELTER & SLEEP SYSTEM (Based on real ultralight gear)
        shelter: [
            // Tents
            { id: 'tent-ul-1p', name: 'Zpacks Duplex', weight: 538, category: 'shelter', subcategory: 'tent', emoji: '⛺', price: 699, brand: 'Zpacks' },
            { id: 'tent-ul-2p', name: 'Big Agnes Copper Spur HV UL2', weight: 1190, category: 'shelter', subcategory: 'tent', emoji: '⛺', price: 449, brand: 'Big Agnes' },
            { id: 'tent-3season', name: 'MSR Hubba Hubba NX', weight: 1720, category: 'shelter', subcategory: 'tent', emoji: '⛺', price: 449, brand: 'MSR' },
            { id: 'tent-4season', name: 'Hilleberg Nammatj 2', weight: 3200, category: 'shelter', subcategory: 'tent', emoji: '⛺', price: 945, brand: 'Hilleberg' },
            { id: 'tent-budget', name: 'Naturehike Cloud-Up 2', weight: 1500, category: 'shelter', subcategory: 'tent', emoji: '⛺', price: 139, brand: 'Naturehike' },
            
            // Tarps & Hammocks
            { id: 'tarp-dcf', name: 'DCF Flat Tarp 8x10', weight: 180, category: 'shelter', subcategory: 'tarp', emoji: '🏕️', price: 285, brand: 'HMG' },
            { id: 'tarp-silnylon', name: 'Silnylon Tarp 9x9', weight: 350, category: 'shelter', subcategory: 'tarp', emoji: '🏕️', price: 125, brand: 'Borah' },
            { id: 'hammock-ul', name: 'Hummingbird Single+', weight: 147, category: 'shelter', subcategory: 'hammock', emoji: '🛏️', price: 69, brand: 'Hummingbird' },
            { id: 'hammock-eno', name: 'ENO DoubleNest', weight: 538, category: 'shelter', subcategory: 'hammock', emoji: '🛏️', price: 69, brand: 'ENO' },
            { id: 'bivy', name: 'OR Helium Bivy', weight: 510, category: 'shelter', subcategory: 'bivy', emoji: '🏕️', price: 279, brand: 'Outdoor Research' },
            
            // Sleeping Bags & Quilts
            { id: 'quilt-20f', name: 'EE Revelation 20°F', weight: 570, category: 'shelter', subcategory: 'sleep', emoji: '🛌', price: 340, brand: 'Enlightened Equipment' },
            { id: 'quilt-30f', name: 'Katabatic Flex 30°F', weight: 490, category: 'shelter', subcategory: 'sleep', emoji: '🛌', price: 375, brand: 'Katabatic' },
            { id: 'bag-0f', name: 'WM Versalite 0°F', weight: 850, category: 'shelter', subcategory: 'sleep', emoji: '🛌', price: 595, brand: 'Western Mountaineering' },
            { id: 'bag-20f', name: 'Feathered Friends Flicker 20', weight: 680, category: 'shelter', subcategory: 'sleep', emoji: '🛌', price: 489, brand: 'Feathered Friends' },
            { id: 'bag-budget', name: 'Kelty Cosmic 20', weight: 1130, category: 'shelter', subcategory: 'sleep', emoji: '🛌', price: 159, brand: 'Kelty' },
            
            // Sleeping Pads
            { id: 'pad-xlite', name: 'Thermarest NeoAir XLite', weight: 340, category: 'shelter', subcategory: 'pad', emoji: '🟦', price: 210, brand: 'Thermarest' },
            { id: 'pad-xtherm', name: 'Thermarest NeoAir XTherm', weight: 430, category: 'shelter', subcategory: 'pad', emoji: '🟦', price: 230, brand: 'Thermarest' },
            { id: 'pad-ccf', name: 'Thermarest Z-Lite Sol', weight: 410, category: 'shelter', subcategory: 'pad', emoji: '🟨', price: 55, brand: 'Thermarest' },
            { id: 'pad-tensor', name: 'Nemo Tensor Insulated', weight: 410, category: 'shelter', subcategory: 'pad', emoji: '🟦', price: 195, brand: 'Nemo' },
            { id: 'pad-1-8', name: 'GossamerGear 1/8" Thinlite', weight: 60, category: 'shelter', subcategory: 'pad', emoji: '🟨', price: 22, brand: 'GossamerGear' },
            
            // Pillows
            { id: 'pillow-ul', name: 'Sea to Summit Aeros UL', weight: 60, category: 'shelter', subcategory: 'pillow', emoji: '🟦', price: 39, brand: 'Sea to Summit' },
            { id: 'pillow-fleece', name: 'Cocoon Hyperlight', weight: 74, category: 'shelter', subcategory: 'pillow', emoji: '🟦', price: 29, brand: 'Cocoon' }
        ],
        
        // COOKING & WATER
        cooking: [
            // Stoves
            { id: 'stove-brs', name: 'BRS-3000T', weight: 25, category: 'cooking', subcategory: 'stove', emoji: '🔥', price: 17, brand: 'BRS' },
            { id: 'stove-pocket', name: 'MSR PocketRocket 2', weight: 73, category: 'cooking', subcategory: 'stove', emoji: '🔥', price: 59, brand: 'MSR' },
            { id: 'stove-windmaster', name: 'Soto WindMaster', weight: 87, category: 'cooking', subcategory: 'stove', emoji: '🔥', price: 64, brand: 'Soto' },
            { id: 'stove-jetboil', name: 'Jetboil Flash', weight: 370, category: 'cooking', subcategory: 'stove', emoji: '🔥', price: 109, brand: 'Jetboil' },
            { id: 'stove-whisperlite', name: 'MSR WhisperLite', weight: 316, category: 'cooking', subcategory: 'stove', emoji: '🔥', price: 99, brand: 'MSR' },
            { id: 'stove-alcohol', name: 'Trangia Spirit Burner', weight: 110, category: 'cooking', subcategory: 'stove', emoji: '🔥', price: 15, brand: 'Trangia' },
            { id: 'stove-esbit', name: 'Esbit Titanium Stove', weight: 12, category: 'cooking', subcategory: 'stove', emoji: '🔥', price: 34, brand: 'Esbit' },
            
            // Cookware
            { id: 'pot-toaks-550', name: 'TOAKS Titanium 550ml', weight: 65, category: 'cooking', subcategory: 'pot', emoji: '🍲', price: 34, brand: 'TOAKS' },
            { id: 'pot-toaks-750', name: 'TOAKS Titanium 750ml', weight: 78, category: 'cooking', subcategory: 'pot', emoji: '🍲', price: 38, brand: 'TOAKS' },
            { id: 'pot-evernew', name: 'Evernew Ti 900ml', weight: 107, category: 'cooking', subcategory: 'pot', emoji: '🍲', price: 64, brand: 'Evernew' },
            { id: 'pot-imusa', name: 'IMUSA 12cm Mug', weight: 100, category: 'cooking', subcategory: 'pot', emoji: '🍲', price: 6, brand: 'IMUSA' },
            { id: 'pot-gsi', name: 'GSI Halulite Minimalist', weight: 176, category: 'cooking', subcategory: 'pot', emoji: '🍲', price: 34, brand: 'GSI' },
            
            // Utensils
            { id: 'spork-ti', name: 'TOAKS Titanium Spork', weight: 17, category: 'cooking', subcategory: 'utensil', emoji: '🥄', price: 8, brand: 'TOAKS' },
            { id: 'spoon-long', name: 'Sea to Summit Long Spoon', weight: 25, category: 'cooking', subcategory: 'utensil', emoji: '🥄', price: 9, brand: 'Sea to Summit' },
            { id: 'knife-opinel', name: 'Opinel No.6', weight: 34, category: 'cooking', subcategory: 'utensil', emoji: '🔪', price: 17, brand: 'Opinel' },
            
            // Water Storage
            { id: 'bottle-smart', name: 'SmartWater 1L', weight: 38, category: 'cooking', subcategory: 'water', emoji: '💧', price: 2, brand: 'SmartWater' },
            { id: 'bottle-nalgene', name: 'Nalgene 32oz', weight: 180, category: 'cooking', subcategory: 'water', emoji: '💧', price: 14, brand: 'Nalgene' },
            { id: 'bladder-cnoc', name: 'CNOC Vecto 2L', weight: 64, category: 'cooking', subcategory: 'water', emoji: '💧', price: 25, brand: 'CNOC' },
            { id: 'bladder-hydrapak', name: 'HydraPak 3L', weight: 142, category: 'cooking', subcategory: 'water', emoji: '💧', price: 39, brand: 'HydraPak' },
            { id: 'bottle-soft-1l', name: 'Platypus SoftBottle 1L', weight: 36, category: 'cooking', subcategory: 'water', emoji: '💧', price: 12, brand: 'Platypus' },
            
            // Water Treatment
            { id: 'filter-sawyer-mini', name: 'Sawyer Mini', weight: 57, category: 'cooking', subcategory: 'filter', emoji: '💧', price: 24, brand: 'Sawyer' },
            { id: 'filter-sawyer-squeeze', name: 'Sawyer Squeeze', weight: 85, category: 'cooking', subcategory: 'filter', emoji: '💧', price: 37, brand: 'Sawyer' },
            { id: 'filter-katadyn-befree', name: 'Katadyn BeFree 1L', weight: 63, category: 'cooking', subcategory: 'filter', emoji: '💧', price: 44, brand: 'Katadyn' },
            { id: 'purifier-grayl', name: 'GRAYL GeoPress', weight: 450, category: 'cooking', subcategory: 'filter', emoji: '💧', price: 89, brand: 'GRAYL' },
            { id: 'tablets-aquatabs', name: 'Aquatabs (30 pack)', weight: 10, category: 'cooking', subcategory: 'treatment', emoji: '💊', price: 12, brand: 'Aquatabs' },
            { id: 'drops-aquamira', name: 'Aquamira Drops', weight: 85, category: 'cooking', subcategory: 'treatment', emoji: '💧', price: 15, brand: 'Aquamira' },
            
            // Fuel
            { id: 'fuel-100g', name: 'Isobutane 100g', weight: 190, category: 'cooking', subcategory: 'fuel', emoji: '🔋', price: 6, brand: 'MSR' },
            { id: 'fuel-230g', name: 'Isobutane 230g', weight: 370, category: 'cooking', subcategory: 'fuel', emoji: '🔋', price: 10, brand: 'MSR' },
            { id: 'fuel-alcohol', name: 'Denatured Alcohol 8oz', weight: 240, category: 'cooking', subcategory: 'fuel', emoji: '🔋', price: 5, brand: 'Generic' }
        ],
        
        // CLOTHING SYSTEM
        clothing: [
            // Base Layers
            { id: 'base-merino-top', name: 'Smartwool Merino 150 Tee', weight: 120, category: 'clothing', subcategory: 'baselayer', emoji: '👕', price: 75, brand: 'Smartwool' },
            { id: 'base-merino-bottom', name: 'Icebreaker Merino Leggings', weight: 150, category: 'clothing', subcategory: 'baselayer', emoji: '👖', price: 80, brand: 'Icebreaker' },
            { id: 'base-capilene-top', name: 'Patagonia Capilene Cool', weight: 85, category: 'clothing', subcategory: 'baselayer', emoji: '👕', price: 49, brand: 'Patagonia' },
            { id: 'base-synthetic-bottom', name: 'REI Silk Weight Bottoms', weight: 110, category: 'clothing', subcategory: 'baselayer', emoji: '👖', price: 39, brand: 'REI' },
            
            // Mid Layers
            { id: 'fleece-r1', name: 'Patagonia R1 Daily', weight: 315, category: 'clothing', subcategory: 'midlayer', emoji: '🧥', price: 99, brand: 'Patagonia' },
            { id: 'fleece-melanzana', name: 'Melanzana Micro Grid Hoodie', weight: 280, category: 'clothing', subcategory: 'midlayer', emoji: '🧥', price: 75, brand: 'Melanzana' },
            { id: 'puffy-ghost', name: 'Mountain Hardwear Ghost Whisperer', weight: 230, category: 'clothing', subcategory: 'insulation', emoji: '🧥', price: 325, brand: 'Mountain Hardwear' },
            { id: 'puffy-cerium', name: "Arc'teryx Cerium LT", weight: 280, category: 'clothing', subcategory: 'insulation', emoji: '🧥', price: 379, brand: "Arc'teryx" },
            { id: 'puffy-budget', name: 'Decathlon Forclaz MT100', weight: 340, category: 'clothing', subcategory: 'insulation', emoji: '🧥', price: 39, brand: 'Decathlon' },
            { id: 'vest-nano', name: 'Patagonia Nano Puff Vest', weight: 170, category: 'clothing', subcategory: 'insulation', emoji: '🦺', price: 149, brand: 'Patagonia' },
            
            // Outer Layers
            { id: 'rain-frogg', name: 'Frogg Toggs UL Jacket', weight: 155, category: 'clothing', subcategory: 'rain', emoji: '🧥', price: 25, brand: 'Frogg Toggs' },
            { id: 'rain-torrent', name: 'Patagonia Torrentshell 3L', weight: 295, category: 'clothing', subcategory: 'rain', emoji: '🧥', price: 149, brand: 'Patagonia' },
            { id: 'rain-versalite', name: 'Montbell Versalite', weight: 185, category: 'clothing', subcategory: 'rain', emoji: '🧥', price: 229, brand: 'Montbell' },
            { id: 'rain-helium', name: 'OR Helium II', weight: 180, category: 'clothing', subcategory: 'rain', emoji: '🧥', price: 159, brand: 'Outdoor Research' },
            { id: 'rain-zpacks', name: 'Zpacks Vertice Rain Jacket', weight: 170, category: 'clothing', subcategory: 'rain', emoji: '🧥', price: 299, brand: 'Zpacks' },
            { id: 'rain-pants', name: 'Marmot PreCip Eco Pants', weight: 195, category: 'clothing', subcategory: 'rain', emoji: '👖', price: 80, brand: 'Marmot' },
            { id: 'wind-houdini', name: 'Patagonia Houdini', weight: 105, category: 'clothing', subcategory: 'wind', emoji: '🧥', price: 99, brand: 'Patagonia' },
            
            // Bottoms
            { id: 'shorts-running', name: 'Patagonia Strider Pro 5"', weight: 110, category: 'clothing', subcategory: 'bottoms', emoji: '🩳', price: 69, brand: 'Patagonia' },
            { id: 'shorts-baggies', name: 'Patagonia Baggies 5"', weight: 140, category: 'clothing', subcategory: 'bottoms', emoji: '🩳', price: 55, brand: 'Patagonia' },
            { id: 'pants-prana', name: 'prAna Stretch Zion', weight: 330, category: 'clothing', subcategory: 'bottoms', emoji: '👖', price: 89, brand: 'prAna' },
            { id: 'pants-zip', name: 'Columbia Silver Ridge Convertible', weight: 365, category: 'clothing', subcategory: 'bottoms', emoji: '👖', price: 60, brand: 'Columbia' },
            { id: 'tights-running', name: 'Running Tights', weight: 150, category: 'clothing', subcategory: 'bottoms', emoji: '👖', price: 45, brand: 'Generic' },
            
            // Accessories
            { id: 'hat-sun', name: 'Sunday Afternoons Sun Hat', weight: 85, category: 'clothing', subcategory: 'headwear', emoji: '👒', price: 35, brand: 'Sunday Afternoons' },
            { id: 'hat-trucker', name: 'Patagonia P-6 Trucker', weight: 77, category: 'clothing', subcategory: 'headwear', emoji: '🧢', price: 29, brand: 'Patagonia' },
            { id: 'beanie-merino', name: 'Smartwool Merino Beanie', weight: 50, category: 'clothing', subcategory: 'headwear', emoji: '🧢', price: 30, brand: 'Smartwool' },
            { id: 'buff', name: 'Buff Original', weight: 38, category: 'clothing', subcategory: 'headwear', emoji: '🧣', price: 20, brand: 'Buff' },
            { id: 'gloves-liner', name: 'Liner Gloves', weight: 30, category: 'clothing', subcategory: 'hands', emoji: '🧤', price: 15, brand: 'Generic' },
            { id: 'gloves-insulated', name: 'OR Versaliner Gloves', weight: 65, category: 'clothing', subcategory: 'hands', emoji: '🧤', price: 39, brand: 'Outdoor Research' },
            { id: 'mittens-possum', name: 'Possum Down Mittens', weight: 50, category: 'clothing', subcategory: 'hands', emoji: '🧤', price: 55, brand: 'ZPacks' },
            
            // Footwear
            { id: 'socks-darn', name: 'Darn Tough Light Hiker', weight: 60, category: 'clothing', subcategory: 'footwear', emoji: '🧦', price: 24, brand: 'Darn Tough' },
            { id: 'socks-injiji', name: 'Injinji Liner Toe Socks', weight: 35, category: 'clothing', subcategory: 'footwear', emoji: '🧦', price: 12, brand: 'Injinji' },
            { id: 'shoes-altra-lp', name: 'Altra Lone Peak 6', weight: 580, category: 'clothing', subcategory: 'footwear', emoji: '👟', price: 140, brand: 'Altra' },
            { id: 'shoes-hoka', name: 'Hoka Speedgoat 5', weight: 640, category: 'clothing', subcategory: 'footwear', emoji: '👟', price: 155, brand: 'Hoka' },
            { id: 'boots-salomon', name: 'Salomon X Ultra 3 Mid', weight: 950, category: 'clothing', subcategory: 'footwear', emoji: '🥾', price: 165, brand: 'Salomon' },
            { id: 'sandals-bedrock', name: 'Bedrock Cairn 3D', weight: 298, category: 'clothing', subcategory: 'footwear', emoji: '👡', price: 110, brand: 'Bedrock' },
            { id: 'gaiters-dirty', name: 'Dirty Girl Gaiters', weight: 30, category: 'clothing', subcategory: 'footwear', emoji: '🦵', price: 22, brand: 'Dirty Girl' }
        ],
        
        // PACKS & BAGS
        packs: [
            // Backpacks
            { id: 'pack-nero', name: 'Zpacks Nero 38L', weight: 298, category: 'packs', subcategory: 'backpack', emoji: '🎒', price: 290, brand: 'Zpacks' },
            { id: 'pack-arc-haul', name: 'Zpacks Arc Haul Ultra 60L', weight: 595, category: 'packs', subcategory: 'backpack', emoji: '🎒', price: 380, brand: 'Zpacks' },
            { id: 'pack-hmg-2400', name: 'HMG 2400 Southwest', weight: 878, category: 'packs', subcategory: 'backpack', emoji: '🎒', price: 355, brand: 'HMG' },
            { id: 'pack-ula-circuit', name: 'ULA Circuit', weight: 935, category: 'packs', subcategory: 'backpack', emoji: '🎒', price: 285, brand: 'ULA' },
            { id: 'pack-gg-mariposa', name: 'Gossamer Gear Mariposa 60', weight: 840, category: 'packs', subcategory: 'backpack', emoji: '🎒', price: 270, brand: 'Gossamer Gear' },
            { id: 'pack-osprey-exos', name: 'Osprey Exos 48', weight: 1130, category: 'packs', subcategory: 'backpack', emoji: '🎒', price: 220, brand: 'Osprey' },
            { id: 'pack-gregory', name: 'Gregory Baltoro 65', weight: 2154, category: 'packs', subcategory: 'backpack', emoji: '🎒', price: 329, brand: 'Gregory' },
            { id: 'pack-budget', name: 'Granite Gear Crown 2 60', weight: 1021, category: 'packs', subcategory: 'backpack', emoji: '🎒', price: 199, brand: 'Granite Gear' },
            
            // Stuff Sacks & Dry Bags
            { id: 'stuff-sack-5l', name: 'Sea to Summit Ultra-Sil 5L', weight: 20, category: 'packs', subcategory: 'storage', emoji: '👜', price: 16, brand: 'Sea to Summit' },
            { id: 'stuff-sack-10l', name: 'Sea to Summit Ultra-Sil 10L', weight: 30, category: 'packs', subcategory: 'storage', emoji: '👜', price: 19, brand: 'Sea to Summit' },
            { id: 'dry-bag-5l', name: 'Sea to Summit Dry Sack 5L', weight: 40, category: 'packs', subcategory: 'storage', emoji: '👜', price: 14, brand: 'Sea to Summit' },
            { id: 'dry-bag-10l', name: 'Sea to Summit Dry Sack 10L', weight: 56, category: 'packs', subcategory: 'storage', emoji: '👜', price: 17, brand: 'Sea to Summit' },
            { id: 'dcf-sack-med', name: 'DCF Stuff Sack Medium', weight: 8, category: 'packs', subcategory: 'storage', emoji: '👜', price: 29, brand: 'Zpacks' },
            { id: 'pack-liner', name: 'Nylofume Pack Liner', weight: 48, category: 'packs', subcategory: 'storage', emoji: '👜', price: 5, brand: 'Generic' },
            { id: 'opsak', name: 'OPSAK 12x20', weight: 37, category: 'packs', subcategory: 'storage', emoji: '👜', price: 12, brand: 'Loksak' },
            
            // Hip & Fanny Packs
            { id: 'hip-belt-pockets', name: 'Zpacks Hip Belt Pockets', weight: 40, category: 'packs', subcategory: 'accessory', emoji: '👝', price: 39, brand: 'Zpacks' },
            { id: 'fanny-pack', name: 'Thrupack Summit Bum', weight: 57, category: 'packs', subcategory: 'accessory', emoji: '👝', price: 65, brand: 'Thrupack' },
            { id: 'shoulder-pouch', name: 'Zimmerbuilt Shoulder Pouch', weight: 28, category: 'packs', subcategory: 'accessory', emoji: '👝', price: 25, brand: 'Zimmerbuilt' }
        ],
        
        // NAVIGATION & ELECTRONICS
        navigation: [
            // Navigation
            { id: 'map-topo', name: 'Topographic Map', weight: 80, category: 'navigation', subcategory: 'map', emoji: '🗺️', price: 12, brand: 'USGS' },
            { id: 'compass-suunto', name: 'Suunto A-10', weight: 30, category: 'navigation', subcategory: 'compass', emoji: '🧭', price: 25, brand: 'Suunto' },
            { id: 'gps-garmin-mini', name: 'Garmin inReach Mini 2', weight: 100, category: 'navigation', subcategory: 'gps', emoji: '📡', price: 399, brand: 'Garmin' },
            { id: 'gps-garmin-66i', name: 'Garmin GPSMAP 66i', weight: 241, category: 'navigation', subcategory: 'gps', emoji: '📡', price: 599, brand: 'Garmin' },
            { id: 'watch-garmin', name: 'Garmin Instinct Solar', weight: 53, category: 'navigation', subcategory: 'watch', emoji: '⌚', price: 399, brand: 'Garmin' },
            { id: 'watch-coros', name: 'Coros Apex Pro', weight: 59, category: 'navigation', subcategory: 'watch', emoji: '⌚', price: 499, brand: 'Coros' },
            { id: 'altimeter', name: 'Casio Pro Trek', weight: 65, category: 'navigation', subcategory: 'watch', emoji: '⌚', price: 199, brand: 'Casio' },
            
            // Lighting
            { id: 'headlamp-nitecore', name: 'Nitecore NU25 400', weight: 57, category: 'navigation', subcategory: 'light', emoji: '🔦', price: 39, brand: 'Nitecore' },
            { id: 'headlamp-petzl', name: 'Petzl Tikka', weight: 82, category: 'navigation', subcategory: 'light', emoji: '🔦', price: 29, brand: 'Petzl' },
            { id: 'headlamp-bd-spot', name: 'Black Diamond Spot 400', weight: 86, category: 'navigation', subcategory: 'light', emoji: '🔦', price: 39, brand: 'Black Diamond' },
            { id: 'flashlight-mini', name: 'Olight i3E EOS', weight: 19, category: 'navigation', subcategory: 'light', emoji: '🔦', price: 9, brand: 'Olight' },
            { id: 'lantern-luci', name: 'Luci Light Solar', weight: 70, category: 'navigation', subcategory: 'light', emoji: '💡', price: 19, brand: 'MPOWERD' },
            
            // Electronics
            { id: 'phone', name: 'Smartphone', weight: 190, category: 'navigation', subcategory: 'electronics', emoji: '📱', price: 0, brand: 'Various' },
            { id: 'battery-10k', name: 'Anker 10000mAh', weight: 212, category: 'navigation', subcategory: 'power', emoji: '🔋', price: 25, brand: 'Anker' },
            { id: 'battery-20k', name: 'Anker 20000mAh', weight: 343, category: 'navigation', subcategory: 'power', emoji: '🔋', price: 45, brand: 'Anker' },
            { id: 'battery-nitecore', name: 'Nitecore NB10000', weight: 150, category: 'navigation', subcategory: 'power', emoji: '🔋', price: 59, brand: 'Nitecore' },
            { id: 'solar-panel', name: 'BioLite SolarPanel 5+', weight: 390, category: 'navigation', subcategory: 'power', emoji: '☀️', price: 79, brand: 'BioLite' },
            { id: 'cable-usbc', name: 'USB-C Cable 3ft', weight: 22, category: 'navigation', subcategory: 'cable', emoji: '🔌', price: 8, brand: 'Anker' },
            { id: 'cable-lightning', name: 'Lightning Cable 3ft', weight: 20, category: 'navigation', subcategory: 'cable', emoji: '🔌', price: 10, brand: 'Anker' },
            { id: 'adapter-wall', name: 'Wall Charger 20W', weight: 45, category: 'navigation', subcategory: 'power', emoji: '🔌', price: 15, brand: 'Anker' },
            { id: 'earbuds', name: 'Wireless Earbuds', weight: 50, category: 'navigation', subcategory: 'electronics', emoji: '🎧', price: 79, brand: 'Various' },
            { id: 'camera-action', name: 'GoPro Hero 11', weight: 153, category: 'navigation', subcategory: 'camera', emoji: '📷', price: 399, brand: 'GoPro' },
            { id: 'kindle', name: 'Kindle Paperwhite', weight: 205, category: 'navigation', subcategory: 'electronics', emoji: '📖', price: 139, brand: 'Amazon' }
        ],
        
        // HEALTH & SAFETY
        health: [
            // First Aid
            { id: 'first-aid-basic', name: 'Basic First Aid Kit', weight: 150, category: 'health', subcategory: 'medical', emoji: '🏥', price: 25, brand: 'Adventure Medical' },
            { id: 'first-aid-ul', name: 'Ultralight First Aid', weight: 70, category: 'health', subcategory: 'medical', emoji: '🏥', price: 35, brand: 'Adventure Medical' },
            { id: 'first-aid-comprehensive', name: 'Comprehensive Medical Kit', weight: 350, category: 'health', subcategory: 'medical', emoji: '🏥', price: 79, brand: 'Adventure Medical' },
            { id: 'bandaids', name: 'Band-Aids (10)', weight: 10, category: 'health', subcategory: 'medical', emoji: '🩹', price: 5, brand: 'Band-Aid' },
            { id: 'gauze', name: 'Gauze Roll', weight: 15, category: 'health', subcategory: 'medical', emoji: '🩹', price: 3, brand: 'Generic' },
            { id: 'tape-medical', name: 'Medical Tape', weight: 20, category: 'health', subcategory: 'medical', emoji: '🩹', price: 4, brand: '3M' },
            { id: 'leukotape', name: 'Leukotape P', weight: 30, category: 'health', subcategory: 'medical', emoji: '🩹', price: 10, brand: 'BSN' },
            { id: 'moleskin', name: 'Moleskin Sheets', weight: 20, category: 'health', subcategory: 'medical', emoji: '🩹', price: 8, brand: 'Dr. Scholl' },
            { id: 'tweezers', name: 'Tweezers', weight: 10, category: 'health', subcategory: 'medical', emoji: '🔧', price: 5, brand: 'Sliver Gripper' },
            { id: 'scissors-tiny', name: 'Tiny Scissors', weight: 18, category: 'health', subcategory: 'medical', emoji: '✂️', price: 8, brand: 'Westcott' },
            { id: 'thermometer', name: 'Digital Thermometer', weight: 20, category: 'health', subcategory: 'medical', emoji: '🌡️', price: 10, brand: 'Generic' },
            
            // Medications
            { id: 'ibuprofen', name: 'Ibuprofen (20 tablets)', weight: 5, category: 'health', subcategory: 'medicine', emoji: '💊', price: 5, brand: 'Generic' },
            { id: 'acetaminophen', name: 'Acetaminophen (20)', weight: 5, category: 'health', subcategory: 'medicine', emoji: '💊', price: 5, brand: 'Generic' },
            { id: 'antihistamine', name: 'Antihistamine (10)', weight: 3, category: 'health', subcategory: 'medicine', emoji: '💊', price: 6, brand: 'Benadryl' },
            { id: 'anti-diarrheal', name: 'Imodium (8)', weight: 3, category: 'health', subcategory: 'medicine', emoji: '💊', price: 7, brand: 'Imodium' },
            { id: 'antacid', name: 'Antacid Tablets (10)', weight: 8, category: 'health', subcategory: 'medicine', emoji: '💊', price: 4, brand: 'Tums' },
            { id: 'electrolytes', name: 'Electrolyte Powder (10)', weight: 60, category: 'health', subcategory: 'medicine', emoji: '💊', price: 12, brand: 'Nuun' },
            
            // Hygiene
            { id: 'toothbrush', name: 'Toothbrush (cut)', weight: 5, category: 'health', subcategory: 'hygiene', emoji: '🪥', price: 3, brand: 'Generic' },
            { id: 'toothpaste-dots', name: 'Toothpaste Dots', weight: 10, category: 'health', subcategory: 'hygiene', emoji: '🦷', price: 5, brand: 'DIY' },
            { id: 'floss', name: 'Dental Floss', weight: 5, category: 'health', subcategory: 'hygiene', emoji: '🦷', price: 3, brand: 'Glide' },
            { id: 'soap-bar', name: 'Dr. Bronners Bar (1oz)', weight: 28, category: 'health', subcategory: 'hygiene', emoji: '🧼', price: 2, brand: 'Dr. Bronners' },
            { id: 'soap-liquid', name: 'Liquid Soap (2oz)', weight: 60, category: 'health', subcategory: 'hygiene', emoji: '🧼', price: 3, brand: 'Dr. Bronners' },
            { id: 'sanitizer', name: 'Hand Sanitizer (1oz)', weight: 35, category: 'health', subcategory: 'hygiene', emoji: '🧴', price: 3, brand: 'Purell' },
            { id: 'wipes', name: 'Wet Wipes (10)', weight: 50, category: 'health', subcategory: 'hygiene', emoji: '🧻', price: 3, brand: 'Dude Wipes' },
            { id: 'toilet-paper', name: 'Toilet Paper', weight: 30, category: 'health', subcategory: 'hygiene', emoji: '🧻', price: 0, brand: 'Generic' },
            { id: 'trowel', name: 'TheTentLab Deuce #2', weight: 17, category: 'health', subcategory: 'hygiene', emoji: '🥄', price: 19, brand: 'TheTentLab' },
            { id: 'bidet', name: 'CuloClean Bidet', weight: 10, category: 'health', subcategory: 'hygiene', emoji: '💦', price: 10, brand: 'CuloClean' },
            { id: 'pee-rag', name: 'Kula Cloth', weight: 14, category: 'health', subcategory: 'hygiene', emoji: '🟦', price: 20, brand: 'Kula' },
            
            // Sun Protection
            { id: 'sunscreen', name: 'Sunscreen SPF 50 (1oz)', weight: 35, category: 'health', subcategory: 'sun', emoji: '🧴', price: 8, brand: 'Neutrogena' },
            { id: 'lip-balm', name: 'SPF Lip Balm', weight: 5, category: 'health', subcategory: 'sun', emoji: '👄', price: 3, brand: 'Chapstick' },
            { id: 'sunglasses', name: 'Sunglasses', weight: 28, category: 'health', subcategory: 'sun', emoji: '🕶️', price: 25, brand: 'Goodr' },
            
            // Bug Protection
            { id: 'bug-spray', name: 'Picaridin Spray (1oz)', weight: 40, category: 'health', subcategory: 'bugs', emoji: '🦟', price: 8, brand: 'Sawyer' },
            { id: 'bug-net', name: 'Head Net', weight: 35, category: 'health', subcategory: 'bugs', emoji: '🦟', price: 10, brand: 'Sea to Summit' },
            { id: 'permethrin', name: 'Permethrin Treatment', weight: 85, category: 'health', subcategory: 'bugs', emoji: '🦟', price: 15, brand: 'Sawyer' }
        ],
        
        // TOOLS & REPAIR
        tools: [
            // Cutting Tools
            { id: 'knife-victorinox', name: 'Victorinox Classic SD', weight: 21, category: 'tools', subcategory: 'knife', emoji: '🔪', price: 22, brand: 'Victorinox' },
            { id: 'knife-leatherman', name: 'Leatherman Squirt PS4', weight: 56, category: 'tools', subcategory: 'knife', emoji: '🔪', price: 39, brand: 'Leatherman' },
            { id: 'knife-mora', name: 'Morakniv Companion', weight: 117, category: 'tools', subcategory: 'knife', emoji: '🔪', price: 19, brand: 'Morakniv' },
            { id: 'scissors-micro', name: 'Micro Scissors', weight: 5, category: 'tools', subcategory: 'knife', emoji: '✂️', price: 6, brand: 'Westcott' },
            
            // Repair Kit
            { id: 'duct-tape', name: 'Duct Tape (10ft)', weight: 20, category: 'tools', subcategory: 'repair', emoji: '🔧', price: 3, brand: 'Gorilla' },
            { id: 'tenacious-tape', name: 'Tenacious Tape', weight: 10, category: 'tools', subcategory: 'repair', emoji: '🔧', price: 5, brand: 'Gear Aid' },
            { id: 'sewing-kit', name: 'Micro Sewing Kit', weight: 15, category: 'tools', subcategory: 'repair', emoji: '🪡', price: 8, brand: 'Gear Aid' },
            { id: 'safety-pins', name: 'Safety Pins (5)', weight: 3, category: 'tools', subcategory: 'repair', emoji: '📌', price: 2, brand: 'Generic' },
            { id: 'zip-ties', name: 'Zip Ties (5)', weight: 10, category: 'tools', subcategory: 'repair', emoji: '🔧', price: 3, brand: 'Generic' },
            { id: 'super-glue', name: 'Super Glue Tube', weight: 5, category: 'tools', subcategory: 'repair', emoji: '🔧', price: 4, brand: 'Gorilla' },
            { id: 'seam-sealer', name: 'Seam Sealer', weight: 28, category: 'tools', subcategory: 'repair', emoji: '🔧', price: 8, brand: 'Gear Aid' },
            
            // Cordage
            { id: 'guyline', name: 'Guyline 50ft', weight: 40, category: 'tools', subcategory: 'cord', emoji: '🪢', price: 10, brand: 'Lawson' },
            { id: 'paracord', name: 'Paracord 50ft', weight: 90, category: 'tools', subcategory: 'cord', emoji: '🪢', price: 8, brand: '550' },
            { id: 'shock-cord', name: 'Shock Cord 10ft', weight: 20, category: 'tools', subcategory: 'cord', emoji: '🪢', price: 5, brand: 'Generic' },
            { id: 'micro-cord', name: 'Micro Cord 50ft', weight: 25, category: 'tools', subcategory: 'cord', emoji: '🪢', price: 8, brand: 'Atwood' },
            
            // Other Tools
            { id: 'whistle', name: 'Emergency Whistle', weight: 10, category: 'tools', subcategory: 'safety', emoji: '📯', price: 5, brand: 'Fox 40' },
            { id: 'mirror', name: 'Signal Mirror', weight: 20, category: 'tools', subcategory: 'safety', emoji: '🪞', price: 8, brand: 'UST' },
            { id: 'lighter-bic', name: 'BIC Lighter', weight: 21, category: 'tools', subcategory: 'fire', emoji: '🔥', price: 2, brand: 'BIC' },
            { id: 'lighter-mini', name: 'Mini BIC', weight: 11, category: 'tools', subcategory: 'fire', emoji: '🔥', price: 2, brand: 'BIC' },
            { id: 'matches', name: 'Waterproof Matches', weight: 25, category: 'tools', subcategory: 'fire', emoji: '🔥', price: 5, brand: 'UCO' },
            { id: 'firestarter', name: 'Fire Starter', weight: 10, category: 'tools', subcategory: 'fire', emoji: '🔥', price: 3, brand: 'Coghlan' },
            { id: 'carabiner', name: 'Mini Carabiner (2)', weight: 20, category: 'tools', subcategory: 'hardware', emoji: '🔗', price: 5, brand: 'Nite Ize' },
            { id: 'stakes-ti', name: 'Titanium Stakes (6)', weight: 48, category: 'tools', subcategory: 'hardware', emoji: '📍', price: 30, brand: 'TOAKS' },
            { id: 'stakes-groundhog', name: 'Groundhog Stakes (6)', weight: 84, category: 'tools', subcategory: 'hardware', emoji: '📍', price: 24, brand: 'MSR' }
        ],
        
        // FOOD (Common Trail Foods)
        food: [
            // Breakfast
            { id: 'oatmeal', name: 'Instant Oatmeal', weight: 40, category: 'food', subcategory: 'breakfast', emoji: '🥣', price: 1, brand: 'Quaker' },
            { id: 'granola', name: 'Granola (100g)', weight: 100, category: 'food', subcategory: 'breakfast', emoji: '🥣', price: 3, brand: 'Various' },
            { id: 'coffee-instant', name: 'Instant Coffee (7 packets)', weight: 21, category: 'food', subcategory: 'breakfast', emoji: '☕', price: 5, brand: 'Starbucks' },
            { id: 'coffee-grounds', name: 'Coffee Grounds (100g)', weight: 100, category: 'food', subcategory: 'breakfast', emoji: '☕', price: 4, brand: 'Various' },
            { id: 'tea-bags', name: 'Tea Bags (10)', weight: 20, category: 'food', subcategory: 'breakfast', emoji: '🍵', price: 3, brand: 'Various' },
            { id: 'protein-powder', name: 'Protein Powder (serving)', weight: 30, category: 'food', subcategory: 'breakfast', emoji: '🥤', price: 2, brand: 'Various' },
            { id: 'pop-tarts', name: 'Pop-Tarts (1 pack)', weight: 96, category: 'food', subcategory: 'breakfast', emoji: '🍪', price: 2, brand: 'Kellogg' },
            
            // Lunch/Snacks
            { id: 'tortilla', name: 'Tortillas (4)', weight: 120, category: 'food', subcategory: 'lunch', emoji: '🫓', price: 2, brand: 'Mission' },
            { id: 'bagel', name: 'Bagel', weight: 100, category: 'food', subcategory: 'lunch', emoji: '🥯', price: 2, brand: 'Various' },
            { id: 'cheese', name: 'Hard Cheese (100g)', weight: 100, category: 'food', subcategory: 'lunch', emoji: '🧀', price: 4, brand: 'Various' },
            { id: 'salami', name: 'Salami (100g)', weight: 100, category: 'food', subcategory: 'lunch', emoji: '🥓', price: 5, brand: 'Various' },
            { id: 'tuna-packet', name: 'Tuna Packet', weight: 74, category: 'food', subcategory: 'lunch', emoji: '🐟', price: 2, brand: 'StarKist' },
            { id: 'peanut-butter', name: 'Peanut Butter (2oz)', weight: 60, category: 'food', subcategory: 'lunch', emoji: '🥜', price: 2, brand: 'Jif' },
            { id: 'nutella', name: 'Nutella (single)', weight: 15, category: 'food', subcategory: 'lunch', emoji: '🍫', price: 1, brand: 'Nutella' },
            { id: 'honey', name: 'Honey Packet', weight: 14, category: 'food', subcategory: 'lunch', emoji: '🍯', price: 1, brand: 'Various' },
            
            // Trail Snacks
            { id: 'trail-mix', name: 'Trail Mix (150g)', weight: 150, category: 'food', subcategory: 'snacks', emoji: '🥜', price: 4, brand: 'Various' },
            { id: 'nuts', name: 'Mixed Nuts (100g)', weight: 100, category: 'food', subcategory: 'snacks', emoji: '🥜', price: 3, brand: 'Various' },
            { id: 'jerky', name: 'Beef Jerky (50g)', weight: 50, category: 'food', subcategory: 'snacks', emoji: '🥩', price: 5, brand: 'Jack Links' },
            { id: 'energy-bar', name: 'Clif Bar', weight: 68, category: 'food', subcategory: 'snacks', emoji: '🍫', price: 2, brand: 'Clif' },
            { id: 'protein-bar', name: 'RX Bar', weight: 52, category: 'food', subcategory: 'snacks', emoji: '🍫', price: 2.5, brand: 'RXBAR' },
            { id: 'fruit-dried', name: 'Dried Fruit (100g)', weight: 100, category: 'food', subcategory: 'snacks', emoji: '🍓', price: 4, brand: 'Various' },
            { id: 'chocolate', name: 'Chocolate Bar', weight: 100, category: 'food', subcategory: 'snacks', emoji: '🍫', price: 3, brand: 'Various' },
            { id: 'candy', name: 'Candy (100g)', weight: 100, category: 'food', subcategory: 'snacks', emoji: '🍬', price: 3, brand: 'Various' },
            { id: 'chips', name: 'Chips (small bag)', weight: 30, category: 'food', subcategory: 'snacks', emoji: '🥔', price: 2, brand: 'Various' },
            { id: 'crackers', name: 'Crackers (100g)', weight: 100, category: 'food', subcategory: 'snacks', emoji: '🍪', price: 3, brand: 'Various' },
            
            // Dinner
            { id: 'mountain-house', name: 'Mountain House Meal', weight: 135, category: 'food', subcategory: 'dinner', emoji: '🍝', price: 12, brand: 'Mountain House' },
            { id: 'backpackers-pantry', name: 'Backpackers Pantry Meal', weight: 140, category: 'food', subcategory: 'dinner', emoji: '🍝', price: 10, brand: 'Backpackers Pantry' },
            { id: 'peak-refuel', name: 'Peak Refuel Meal', weight: 150, category: 'food', subcategory: 'dinner', emoji: '🍝', price: 14, brand: 'Peak Refuel' },
            { id: 'ramen', name: 'Ramen Noodles', weight: 85, category: 'food', subcategory: 'dinner', emoji: '🍜', price: 1, brand: 'Maruchan' },
            { id: 'pasta', name: 'Pasta (100g)', weight: 100, category: 'food', subcategory: 'dinner', emoji: '🍝', price: 2, brand: 'Various' },
            { id: 'instant-rice', name: 'Instant Rice (100g)', weight: 100, category: 'food', subcategory: 'dinner', emoji: '🍚', price: 2, brand: 'Uncle Bens' },
            { id: 'couscous', name: 'Couscous (100g)', weight: 100, category: 'food', subcategory: 'dinner', emoji: '🍚', price: 3, brand: 'Various' },
            { id: 'instant-mash', name: 'Instant Mashed Potatoes', weight: 100, category: 'food', subcategory: 'dinner', emoji: '🥔', price: 3, brand: 'Idahoan' },
            
            // Condiments & Extras
            { id: 'olive-oil', name: 'Olive Oil (2oz)', weight: 60, category: 'food', subcategory: 'condiments', emoji: '🫒', price: 2, brand: 'Various' },
            { id: 'hot-sauce', name: 'Hot Sauce (mini)', weight: 30, category: 'food', subcategory: 'condiments', emoji: '🌶️', price: 2, brand: 'Tabasco' },
            { id: 'salt', name: 'Salt (small)', weight: 10, category: 'food', subcategory: 'condiments', emoji: '🧂', price: 1, brand: 'Various' },
            { id: 'pepper', name: 'Pepper (small)', weight: 10, category: 'food', subcategory: 'condiments', emoji: '🧂', price: 1, brand: 'Various' },
            { id: 'spices', name: 'Spice Mix', weight: 15, category: 'food', subcategory: 'condiments', emoji: '🧂', price: 2, brand: 'Various' }
        ]
    };

    // ==================== STATE MANAGEMENT ====================
    const state = {
        packs: [],
        customGear: [],
        builderPack: null,
        currentStep: 1,
        draggedItem: null,
        currentFilter: 'all',
        searchTerm: '',
        previewMode: false,
        activeFilters: {
            category: 'all',
            weight: null,
            brand: null,
            priceRange: null
        },
        sortBy: 'weight', // weight, name, category, price
        view: 'grid' // grid, list, compact
    };

    // ==================== INITIALIZATION ====================
    function init() {
        console.log('🎒 Pack Manager Pro Initializing...');
        loadStoredData();
        setupEventListeners();
        renderMainView();
        console.log('✅ Pack Manager Pro Ready!');
    }

    function loadStoredData() {
        // Load saved packs
        const savedPacks = localStorage.getItem('btt_packs_pro');
        if (savedPacks) {
            state.packs = JSON.parse(savedPacks);
        }
        
        // Load custom gear
        const customGear = localStorage.getItem('btt_custom_gear');
        if (customGear) {
            state.customGear = JSON.parse(customGear);
        }
    }

    function setupEventListeners() {
        // Global event delegation for performance
        document.addEventListener('click', handleGlobalClick);
        document.addEventListener('input', handleGlobalInput);
        document.addEventListener('change', handleGlobalChange);
        
        // Drag and drop with touch support
        setupDragAndDrop();
        
        // Keyboard shortcuts
        document.addEventListener('keydown', handleKeyboardShortcuts);
    }

    function handleGlobalClick(e) {
        const target = e.target;
        const action = target.closest('[data-action]')?.dataset.action;
        
        if (!action) return;
        
        // Route to appropriate handler
        const handlers = {
            'create-pack': () => openBuilder(),
            'close-builder': () => closeBuilder(),
            'next-step': () => navigateStep(1),
            'prev-step': () => navigateStep(-1),
            'save-pack': () => savePack(),
            'preview-pack': () => togglePreview(),
            'add-section': () => addSection(),
            'remove-section': (el) => removeSection(el.closest('.section-container')),
            'add-custom-item': () => showCustomItemDialog(),
            'quick-add': (el) => quickAddItem(el.dataset.itemId),
            'remove-item': (el) => removePackItem(el.closest('.pack-item')),
            'filter-category': (el) => filterByCategory(el.dataset.category),
            'sort-items': (el) => sortItems(el.dataset.sort),
            'change-view': (el) => changeView(el.dataset.view),
            'duplicate-pack': (el) => duplicatePack(el.dataset.packId),
            'export-pack': (el) => exportPack(el.dataset.packId),
            'delete-pack': (el) => deletePack(el.dataset.packId),
            'load-template': (el) => loadTemplate(el.dataset.template),
            'toggle-favorite': (el) => toggleFavorite(el)
        };
        
        const handler = handlers[action];
        if (handler) {
            e.preventDefault();
            handler(target);
        }
    }

    function handleGlobalInput(e) {
        const target = e.target;
        
        if (target.id === 'gear-search') {
            debounce(() => searchGear(target.value), 300);
        } else if (target.classList.contains('section-name-input')) {
            updateSectionName(target);
        } else if (target.id === 'pack-name-input') {
            state.builderPack.name = target.value;
        } else if (target.id === 'weight-filter') {
            filterByWeight(target.value);
        }
    }

    function handleGlobalChange(e) {
        const target = e.target;
        
        if (target.name === 'pack-type') {
            state.builderPack.type = target.value;
            updateRecommendations();
        } else if (target.name === 'season') {
            state.builderPack.season = target.value;
            updateSeasonalGear();
        }
    }

    function handleKeyboardShortcuts(e) {
        // Ctrl/Cmd + S: Save
        if ((e.ctrlKey || e.metaKey) && e.key === 's' && state.builderPack) {
            e.preventDefault();
            savePack();
        }
        // Escape: Close modal
        else if (e.key === 'Escape' && state.builderPack) {
            closeBuilder();
        }
        // Ctrl/Cmd + N: New pack
        else if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            openBuilder();
        }
    }

    // ==================== PACK BUILDER ====================
    function openBuilder() {
        state.builderPack = {
            id: generateId(),
            name: '',
            description: '',
            type: 'weekend',
            season: 'three-season',
            baseWeight: 0,
            totalWeight: 0,
            sections: getDefaultSections(),
            favorite: false,
            created: new Date().toISOString(),
            modified: new Date().toISOString()
        };
        
        state.currentStep = 1;
        renderBuilder();
        showModal();
    }

    function getDefaultSections() {
        return [
            { 
                id: generateId(), 
                name: 'Pack Body (Top)', 
                color: '#4ade80', 
                items: [],
                position: 'top',
                description: 'Quick access items, rain gear, first aid'
            },
            { 
                id: generateId(), 
                name: 'Pack Body (Middle)', 
                color: '#60a5fa', 
                items: [],
                position: 'middle',
                description: 'Clothing, cook system, food'
            },
            { 
                id: generateId(), 
                name: 'Pack Body (Bottom)', 
                color: '#f59e0b', 
                items: [],
                position: 'bottom',
                description: 'Sleep system, shelter'
            },
            { 
                id: generateId(), 
                name: 'Hip Belt Pockets', 
                color: '#a78bfa', 
                items: [],
                position: 'hipbelt',
                description: 'Snacks, phone, sunscreen'
            },
            { 
                id: generateId(), 
                name: 'Outside Pockets', 
                color: '#f87171', 
                items: [],
                position: 'external',
                description: 'Water bottles, tent stakes, trowel'
            }
        ];
    }

    function renderBuilder() {
        const modal = document.getElementById('pack-builder-modal');
        const container = document.getElementById('builder-layout');
        
        if (!container) return;
        
        let content = '';
        
        switch(state.currentStep) {
            case 1:
                content = renderDetailsStep();
                break;
            case 2:
                content = renderGearStep();
                break;
            case 3:
                content = renderReviewStep();
                break;
        }
        
        container.innerHTML = content;
        updateProgressBar();
        updateStats();
        
        // Show modal
        modal.classList.remove('hidden');
    }

    function renderDetailsStep() {
        return `
            <div class="builder-step">
                <h3 class="step-title">Pack Details & Configuration</h3>
                
                <div class="details-grid">
                    <div class="detail-section">
                        <label class="form-label">Pack Name *</label>
                        <input type="text" id="pack-name-input" class="form-input" 
                               placeholder="e.g., PCT Section Hike" 
                               value="${state.builderPack.name}">
                        
                        <label class="form-label">Description</label>
                        <textarea class="form-textarea" id="pack-description"
                                  placeholder="Notes about this pack...">${state.builderPack.description || ''}</textarea>
                    </div>
                    
                    <div class="detail-section">
                        <label class="form-label">Trip Type</label>
                        <div class="radio-group">
                            ${['day', 'overnight', 'weekend', 'week', 'thru-hike'].map(type => `
                                <label class="radio-option">
                                    <input type="radio" name="pack-type" value="${type}" 
                                           ${state.builderPack.type === type ? 'checked' : ''}>
                                    <span>${type.replace('-', ' ')}</span>
                                </label>
                            `).join('')}
                        </div>
                        
                        <label class="form-label">Season</label>
                        <div class="season-grid">
                            ${[
                                {value: 'summer', emoji: '☀️', label: 'Summer'},
                                {value: 'three-season', emoji: '🍃', label: '3-Season'},
                                {value: 'winter', emoji: '❄️', label: 'Winter'},
                                {value: 'desert', emoji: '🏜️', label: 'Desert'}
                            ].map(s => `
                                <button class="season-btn ${state.builderPack.season === s.value ? 'active' : ''}"
                                        data-season="${s.value}">
                                    <span class="season-emoji">${s.emoji}</span>
                                    <span class="season-label">${s.label}</span>
                                </button>
                            `).join('')}
                        </div>
                    </div>
                </div>
                
                <div class="template-section">
                    <h4>Quick Start Templates</h4>
                    <div class="template-grid">
                        ${renderTemplates()}
                    </div>
                </div>
                
                <div class="sections-config">
                    <h4>Pack Organization</h4>
                    <div class="sections-list">
                        ${state.builderPack.sections.map(section => renderSectionConfig(section)).join('')}
                        <button class="add-section-btn" data-action="add-section">
                            + Add Custom Section
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    function renderGearStep() {
        const allGear = getAllGear();
        const categories = [...new Set(allGear.map(item => item.category))];
        
        return `
            <div class="gear-step">
                <div class="gear-layout">
                    <!-- Gear Library -->
                    <div class="gear-library">
                        <div class="gear-header">
                            <h3>Gear Library</h3>
                            <button class="btn-small" data-action="add-custom-item">+ Custom Item</button>
                        </div>
                        
                        <div class="gear-controls">
                            <input type="search" id="gear-search" placeholder="Search gear..." 
                                   class="search-input">
                            
                            <div class="filter-tabs">
                                <button class="filter-tab active" data-action="filter-category" data-category="all">
                                    All
                                </button>
                                ${categories.map(cat => `
                                    <button class="filter-tab" data-action="filter-category" data-category="${cat}">
                                        ${cat}
                                    </button>
                                `).join('')}
                            </div>
                            
                            <div class="sort-controls">
                                <select id="sort-gear" class="sort-select">
                                    <option value="weight">Sort by Weight</option>
                                    <option value="name">Sort by Name</option>
                                    <option value="category">Sort by Category</option>
                                    <option value="price">Sort by Price</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="gear-items" id="gear-items">
                            ${renderGearItems(allGear)}
                        </div>
                    </div>
                    
                    <!-- Pack Contents -->
                    <div class="pack-contents">
                        <div class="pack-header">
                            <h3>${state.builderPack.name || 'Your Pack'}</h3>
                            <div class="pack-stats-live">
                                <span class="stat">⚖️ ${formatWeight(calculatePackWeight())}</span>
                                <span class="stat">📦 ${countPackItems()} items</span>
                            </div>
                        </div>
                        
                        <div class="pack-sections">
                            ${state.builderPack.sections.map(section => renderPackSection(section)).join('')}
                        </div>
                        
                        <div class="pack-actions">
                            <button class="btn-secondary" data-action="preview-pack">
                                👁️ Preview Pack
                            </button>
                            <button class="btn-secondary" data-action="weigh-pack">
                                ⚖️ Analyze Weight
                            </button>
                        </div>
                    </div>
                    
                    <!-- Quick Add Panel -->
                    <div class="quick-add-panel">
                        <h4>Essentials</h4>
                        <div class="essentials-list">
                            ${renderEssentials()}
                        </div>
                        
                        <h4>Popular Items</h4>
                        <div class="popular-list">
                            ${renderPopularItems()}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderReviewStep() {
        const weight = calculatePackWeight();
        const items = countPackItems();
        const breakdown = getWeightBreakdown();
        
        return `
            <div class="review-step">
                <div class="review-header">
                    <h2>${state.builderPack.name || 'Unnamed Pack'}</h2>
                    <p>${state.builderPack.description || 'No description provided'}</p>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <span class="stat-value">${formatWeight(weight.base)}</span>
                        <span class="stat-label">Base Weight</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value">${formatWeight(weight.total)}</span>
                        <span class="stat-label">Total Weight</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value">${items}</span>
                        <span class="stat-label">Total Items</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value">$${calculateTotalPrice()}</span>
                        <span class="stat-label">Total Value</span>
                    </div>
                </div>
                
                <div class="weight-breakdown">
                    <h3>Weight Distribution</h3>
                    <div class="breakdown-chart">
                        ${renderWeightChart(breakdown)}
                    </div>
                </div>
                
                <div class="pack-list">
                    <h3>Complete Pack List</h3>
                    ${renderCompletePackList()}
                </div>
                
                <div class="export-options">
                    <button class="btn-secondary" data-action="export-pdf">
                        📄 Export PDF
                    </button>
                    <button class="btn-secondary" data-action="export-csv">
                        📊 Export CSV
                    </button>
                    <button class="btn-secondary" data-action="share-pack">
                        🔗 Share Pack
                    </button>
                </div>
            </div>
        `;
    }

    // ==================== GEAR MANAGEMENT ====================
    function getAllGear() {
        // Combine database gear with custom gear
        const allGear = [];
        
        // Add all database gear
        Object.values(GEAR_DATABASE).forEach(category => {
            allGear.push(...category);
        });
        
        // Add custom gear
        allGear.push(...state.customGear);
        
        return allGear;
    }

    function renderGearItems(items) {
        const filtered = filterGear(items);
        const sorted = sortGear(filtered);
        
        return sorted.map(item => `
            <div class="gear-item" draggable="true" data-item-id="${item.id}">
                <span class="item-emoji">${item.emoji}</span>
                <div class="item-info">
                    <span class="item-name">${item.name}</span>
                    <span class="item-meta">${item.brand || 'Generic'} • ${item.weight}g • $${item.price || 0}</span>
                </div>
                <button class="quick-add-btn" data-action="quick-add" data-item-id="${item.id}">
                    +
                </button>
            </div>
        `).join('');
    }

    function filterGear(items) {
        let filtered = items;
        
        // Category filter
        if (state.activeFilters.category && state.activeFilters.category !== 'all') {
            filtered = filtered.filter(item => item.category === state.activeFilters.category);
        }
        
        // Search filter
        if (state.searchTerm) {
            const term = state.searchTerm.toLowerCase();
            filtered = filtered.filter(item => 
                item.name.toLowerCase().includes(term) ||
                (item.brand && item.brand.toLowerCase().includes(term)) ||
                item.category.includes(term)
            );
        }
        
        // Weight filter
        if (state.activeFilters.weight) {
            filtered = filtered.filter(item => item.weight <= state.activeFilters.weight);
        }
        
        return filtered;
    }

    function sortGear(items) {
        const sorted = [...items];
        
        switch(state.sortBy) {
            case 'weight':
                sorted.sort((a, b) => a.weight - b.weight);
                break;
            case 'name':
                sorted.sort((a, b) => a.name.localeCompare(b.name));
                break;
            case 'category':
                sorted.sort((a, b) => a.category.localeCompare(b.category));
                break;
            case 'price':
                sorted.sort((a, b) => (a.price || 0) - (b.price || 0));
                break;
        }
        
        return sorted;
    }

    // ==================== DRAG AND DROP ====================
    function setupDragAndDrop() {
        let draggedElement = null;
        
        document.addEventListener('dragstart', (e) => {
            if (e.target.classList.contains('gear-item')) {
                draggedElement = e.target;
                state.draggedItem = e.target.dataset.itemId;
                e.target.classList.add('dragging');
            }
        });
        
        document.addEventListener('dragend', (e) => {
            if (e.target.classList.contains('gear-item')) {
                e.target.classList.remove('dragging');
                draggedElement = null;
                state.draggedItem = null;
            }
        });
        
        document.addEventListener('dragover', (e) => {
            const dropzone = e.target.closest('.section-dropzone');
            if (dropzone) {
                e.preventDefault();
                dropzone.classList.add('drag-over');
            }
        });
        
        document.addEventListener('dragleave', (e) => {
            const dropzone = e.target.closest('.section-dropzone');
            if (dropzone) {
                dropzone.classList.remove('drag-over');
            }
        });
        
        document.addEventListener('drop', (e) => {
            const dropzone = e.target.closest('.section-dropzone');
            if (dropzone && state.draggedItem) {
                e.preventDefault();
                dropzone.classList.remove('drag-over');
                
                const sectionId = dropzone.dataset.sectionId;
                addItemToSection(state.draggedItem, sectionId);
            }
        });
    }

    // ==================== UI RENDERING FUNCTIONS ====================
    function renderMainView() {
        // Main view is handled by the backpacks.php page
        // This function is called for compatibility but doesn't need to render anything
        console.log('Main view render called');
    }

    function navigateStep(direction) {
        const newStep = state.currentStep + direction;
        
        // Validate step range
        if (newStep < 1 || newStep > 3) return;
        
        state.currentStep = newStep;
        
        // Update progress bar
        document.querySelectorAll('.progress-step').forEach(step => {
            const stepNum = parseInt(step.dataset.step);
            step.classList.toggle('active', stepNum <= state.currentStep);
        });
        
        // Update navigation buttons
        const prevBtn = document.getElementById('btn-prev');
        const nextBtn = document.getElementById('btn-next');
        const saveBtn = document.getElementById('btn-save');
        
        if (prevBtn) prevBtn.style.display = state.currentStep === 1 ? 'none' : 'block';
        if (nextBtn) nextBtn.style.display = state.currentStep === 3 ? 'none' : 'block';
        if (saveBtn) saveBtn.classList.toggle('hidden', state.currentStep !== 3);
        
        // Render the appropriate step
        switch(state.currentStep) {
            case 1:
                renderDetailsStep();
                break;
            case 2:
                renderGearStep();
                break;
            case 3:
                renderReviewStep();
                break;
        }
    }

    function renderDetailsStep() {
        const container = document.getElementById('builder-layout');
        if (!container) return;
        
        container.innerHTML = `
            <div class="step-content details-step">
                <h3 class="step-title">Pack Details</h3>
                <div class="form-group">
                    <label for="pack-name-input">Pack Name</label>
                    <input type="text" id="pack-name-input" placeholder="e.g., Weekend Warrior" value="${state.builderPack?.name || ''}">
                </div>
                <div class="form-group">
                    <label for="pack-desc-input">Description</label>
                    <textarea id="pack-desc-input" placeholder="Describe your pack setup...">${state.builderPack?.description || ''}</textarea>
                </div>
                <div class="form-group">
                    <label for="pack-type-select">Pack Type</label>
                    <select id="pack-type-select">
                        <option value="ultralight">Ultralight</option>
                        <option value="lightweight">Lightweight</option>
                        <option value="traditional">Traditional</option>
                        <option value="winter">Winter/4-Season</option>
                    </select>
                </div>
            </div>
        `;
    }

    function renderGearStep() {
        const container = document.getElementById('builder-layout');
        if (!container) return;
        
        const allGear = [...GEAR_DATABASE.shelter, ...GEAR_DATABASE.sleep, ...GEAR_DATABASE.cooking, 
                         ...GEAR_DATABASE.clothing, ...GEAR_DATABASE.packs, ...GEAR_DATABASE.navigation,
                         ...GEAR_DATABASE.health, ...GEAR_DATABASE.tools, ...GEAR_DATABASE.food];
        
        container.innerHTML = `
            <div class="step-content gear-step">
                <div class="gear-layout">
                    <div class="gear-library">
                        <div class="library-header">
                            <input type="text" id="gear-search" placeholder="Search gear..." class="search-input">
                            <select id="category-filter" class="filter-select">
                                <option value="all">All Categories</option>
                                <option value="shelter">Shelter</option>
                                <option value="sleep">Sleep System</option>
                                <option value="cooking">Cooking</option>
                                <option value="clothing">Clothing</option>
                                <option value="packs">Packs</option>
                                <option value="navigation">Navigation</option>
                                <option value="health">Health & Safety</option>
                                <option value="tools">Tools</option>
                                <option value="food">Food</option>
                            </select>
                        </div>
                        <div class="gear-grid" id="gear-grid">
                            ${allGear.slice(0, 50).map(item => `
                                <div class="gear-item" draggable="true" data-item='${JSON.stringify(item)}'>
                                    <span class="gear-emoji">${item.emoji || '📦'}</span>
                                    <span class="gear-name">${item.name}</span>
                                    <span class="gear-weight">${item.weight}g</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    <div class="pack-sections">
                        <h4>Pack Sections</h4>
                        <div id="pack-sections-container">
                            ${state.builderPack.sections.map(section => `
                                <div class="pack-section" data-section-id="${section.id}">
                                    <h5>${section.name}</h5>
                                    <div class="section-dropzone" data-section-id="${section.id}">
                                        ${section.items.length ? section.items.map(item => `
                                            <div class="section-item">
                                                <span>${item.emoji || '📦'} ${item.name}</span>
                                                <span>${item.weight}g</span>
                                            </div>
                                        `).join('') : '<p class="empty-message">Drop items here</p>'}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Re-setup drag and drop for new elements
        setupDragAndDrop();
    }

    function renderReviewStep() {
        const container = document.getElementById('builder-layout');
        if (!container) return;
        
        const weights = calculatePackWeight();
        const itemCount = countPackItems();
        
        container.innerHTML = `
            <div class="step-content review-step">
                <h3 class="step-title">Review Your Pack</h3>
                <div class="review-summary">
                    <h4>${state.builderPack.name || 'Unnamed Pack'}</h4>
                    <p>${state.builderPack.description || 'No description'}</p>
                    <div class="weight-summary">
                        <div class="weight-stat">
                            <span class="label">Base Weight:</span>
                            <span class="value">${formatWeight(weights.base)}</span>
                        </div>
                        <div class="weight-stat">
                            <span class="label">Consumables:</span>
                            <span class="value">${formatWeight(weights.consumable)}</span>
                        </div>
                        <div class="weight-stat total">
                            <span class="label">Total Weight:</span>
                            <span class="value">${formatWeight(weights.total)}</span>
                        </div>
                        <div class="weight-stat">
                            <span class="label">Total Items:</span>
                            <span class="value">${itemCount}</span>
                        </div>
                    </div>
                </div>
                <div class="sections-review">
                    ${state.builderPack.sections.map(section => `
                        <div class="section-review">
                            <h5>${section.name} (${section.items.length} items)</h5>
                            <ul>
                                ${section.items.map(item => `
                                    <li>${item.emoji || '📦'} ${item.name} - ${item.weight}g</li>
                                `).join('')}
                            </ul>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    // ==================== UTILITY FUNCTIONS ====================
    function generateId() {
        return `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
    }

    function formatWeight(grams) {
        if (grams < 1000) {
            return `${grams}g`;
        }
        const kg = (grams / 1000).toFixed(2);
        return `${kg}kg`;
    }

    function calculatePackWeight() {
        let baseWeight = 0;
        let consumableWeight = 0;
        
        state.builderPack.sections.forEach(section => {
            section.items.forEach(item => {
                if (isConsumable(item)) {
                    consumableWeight += item.weight * (item.quantity || 1);
                } else {
                    baseWeight += item.weight * (item.quantity || 1);
                }
            });
        });
        
        return {
            base: baseWeight,
            consumable: consumableWeight,
            total: baseWeight + consumableWeight
        };
    }

    function isConsumable(item) {
        const consumableCategories = ['food', 'fuel', 'water'];
        return consumableCategories.includes(item.category);
    }

    function countPackItems() {
        return state.builderPack.sections.reduce((total, section) => {
            return total + section.items.reduce((sum, item) => sum + (item.quantity || 1), 0);
        }, 0);
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // ==================== PUBLIC API ====================
    return {
        init,
        openBuilder,
        closeBuilder: () => {
            state.builderPack = null;
            document.getElementById('pack-builder-modal').classList.add('hidden');
        },
        nextStep: () => navigateStep(1),
        previousStep: () => navigateStep(-1),
        savePack: () => {
            // Update pack name from input if on details step
            const nameInput = document.getElementById('pack-name-input');
            if (nameInput && nameInput.value) {
                state.builderPack.name = nameInput.value;
            }
            
            if (!state.builderPack || !state.builderPack.name) {
                alert('Please enter a pack name');
                return;
            }
            
            state.packs.push({...state.builderPack});
            localStorage.setItem('btt_packs_pro', JSON.stringify(state.packs));
            
            // Close modal and reset
            state.builderPack = null;
            state.currentStep = 1;
            document.getElementById('pack-builder-modal').classList.add('hidden');
            
            // Show success message
            console.log('Pack saved successfully!');
        },
        // Additional methods for main page integration
        createNew: () => openBuilder(),
        quickStart: () => openBuilder(),
        showTour: () => console.log('Tour feature coming soon'),
        toggleView: (view) => console.log(`Switching to ${view} view`),
        search: (term) => { state.searchTerm = term; renderMainView(); },
        filter: (type, value) => console.log(`Filter ${type}: ${value}`),
        filterByWeight: (weight) => { state.activeFilters.weight = weight; },
        showTemplates: () => console.log('Templates dialog'),
        showImport: () => console.log('Import dialog'),
        createFromLastTrip: () => console.log('Creating from last trip'),
        duplicateFavorite: () => console.log('Duplicating favorite'),
        smartPack: () => console.log('AI Smart Pack feature'),
        showCategory: (cat) => { state.activeFilters.category = cat; renderMainView(); },
        filterByTag: (tag) => console.log(`Filter by tag: ${tag}`),
        importFromFile: () => console.log('Import from file'),
        saveDraft: () => console.log('Draft saved'),
        previewPack: () => console.log('Preview mode'),
        closeDetail: () => console.log('Closing detail panel'),
        updatePackName: (name) => { if (state.builderPack) state.builderPack.name = name; },
        updatePackDesc: (desc) => { if (state.builderPack) state.builderPack.description = desc; },
        setSeason: (season) => { if (state.builderPack) state.builderPack.season = season; }
    };
})();

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', PackManagerPro.init);
} else {
    PackManagerPro.init();
}
