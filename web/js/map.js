function pandStyle(feature) {
//                switch (feature.properties.Perceelsbreedte) {
//                    case 'Republican': return {color: "#ff0000"};
//                    case 'Democrat':   return {color: "#0000ff"};
//                }
    if (feature.properties.Perceelsbreedte > 6) {
        return {color: "crimson"};
    } else {
        return {color: "purple"};
    }
}

// displays popup for each pand
function eachPand(feature, layer) {
    var popupContent = properties2Table(feature.properties);
    if (feature.properties && feature.properties.popupContent) {
        popupContent += feature.properties.popupContent;
    }
    layer.bindPopup(popupContent);
}

function properties2Table(properties) {
    //var properties = e.target.feature.properties;
    var html = '<table class="table table-condensed">';
    for (var prop in properties) {
        if (properties.hasOwnProperty(prop)) {
            // or if (Object.prototype.hasOwnProperty.call(obj,prop)) for safety...
            html += "<tr><td>" + prop + "</td><td>" + properties[prop] + "</td></tr>";
        }
    }
    html += '</table>';
    return html;
}

// Add displayinging props to the layer
function handleFeature(feature, layer) {
    layer.on({
        mouseover: showProperties,
        mouseout: clearProperties
    });
}

// Display the properties in a nice steady HTML table
function showProperties(e) {
    var properties = e.target.feature.properties;
    var html = '<table class="table table-bordered table-condensed">';
    for (var prop in properties) {
        if (properties.hasOwnProperty(prop)) {
            // or if (Object.prototype.hasOwnProperty.call(obj,prop)) for safety...
            html += "<tr><td>" + prop + "</td><td>" + properties[prop] + "</td></tr>";
        }
    }
    html += '</table>';
    document.getElementById("properties").innerHTML = html;
    //return html;
}

function clearProperties(e) {
    document.getElementById("properties").innerHTML = "";
}


/**
 * Methods for displaying stuff on the various maps
 *
 **/

// 12 nice differenceColors
var differenceColors = ['#a6cee3', '#1f78b4', '#b2df8a', '#33a02c', '#fb9a99', '#e31a1c', '#fdbf6f', '#ff7f00', '#cab2d6', '#6a3d9a', '#ffff99', '#b15928'];
var divergingColors = ['#a50026', '#d73027', '#f46d43', '#fdae61', '#fee090', '#ffffbf', '#e0f3f8', '#abd9e9', '#74add1', '#4575b4', '#313695'];
var sequentialColors = ['#ffffcc', '#ffeda0', '#fed976', '#feb24c', '#fd8d3c', '#fc4e2a', '#e31a1c', '#bd0026', '#800026'];
var namedColors = ["AliceBlue", "AntiqueWhite", "Aqua", "Aquamarine", "Azure", "Beige", "Bisque", "Black", "BlanchedAlmond", "Blue", "BlueViolet", "Brown", "BurlyWood", "CadetBlue", "Chartreuse", "Chocolate", "Coral", "CornflowerBlue", "Cornsilk", "Crimson", "Cyan", "DarkBlue", "DarkCyan", "DarkGoldenRod", "DarkGray", "DarkGrey", "DarkGreen", "DarkKhaki", "DarkMagenta", "DarkOliveGreen", "Darkorange", "DarkOrchid", "DarkRed", "DarkSalmon", "DarkSeaGreen", "DarkSlateBlue", "DarkSlateGray", "DarkSlateGrey", "DarkTurquoise", "DarkViolet", "DeepPink", "DeepSkyBlue", "DimGray", "DimGrey", "DodgerBlue", "FireBrick", "FloralWhite", "ForestGreen", "Fuchsia", "Gainsboro", "GhostWhite", "Gold", "GoldenRod", "Gray", "Grey", "Green", "GreenYellow", "HoneyDew", "HotPink", "IndianRed", "Indigo", "Ivory", "Khaki", "Lavender", "LavenderBlush", "LawnGreen", "LemonChiffon", "LightBlue", "LightCoral", "LightCyan", "LightGoldenRodYellow", "LightGray", "LightGrey", "LightGreen", "LightPink", "LightSalmon", "LightSeaGreen", "LightSkyBlue", "LightSlateGray", "LightSlateGrey", "LightSteelBlue", "LightYellow", "Lime", "LimeGreen", "Linen", "Magenta", "Maroon", "MediumAquaMarine", "MediumBlue", "MediumOrchid", "MediumPurple", "MediumSeaGreen", "MediumSlateBlue", "MediumSpringGreen", "MediumTurquoise", "MediumVioletRed", "MidnightBlue", "MintCream", "MistyRose", "Moccasin", "NavajoWhite", "Navy", "OldLace", "Olive", "OliveDrab", "Orange", "OrangeRed", "Orchid", "PaleGoldenRod", "PaleGreen", "PaleTurquoise", "PaleVioletRed", "PapayaWhip", "PeachPuff", "Peru", "Pink", "Plum", "PowderBlue", "Purple", "Red", "RosyBrown", "RoyalBlue", "SaddleBrown", "Salmon", "SandyBrown", "SeaGreen", "SeaShell", "Sienna", "Silver", "SkyBlue", "SlateBlue", "SlateGray", "SlateGrey", "Snow", "SpringGreen", "SteelBlue", "Tan", "Teal", "Thistle", "Tomato", "Turquoise", "Violet", "Wheat", "White", "WhiteSmoke", "Yellow", "YellowGreen"];
//var colors = ['#fff7fb','#ece7f2','#d0d1e6','#a6bddb','#74a9cf','#3690c0','#0570b0','#045a8d','#023858'];


function onlyUnique(value, index, self) {
    return self.indexOf(value) === index;
}

// populate filter drop down from dataset columns
function populateFilterOptions(div, columns) {
    var $select = $(div);
    $select.find('option').remove();
    $select.append('<option value="">Kies een kolom</option>');
    $.each(columns, function (key, value) {
        //$select.append('<option value=' + key + '>' + value + '</option>');
        $select.append('<option value=' + value + '>' + value + '</option>');
    });
}

function sortNumber(a, b) {
    return a - b;
}

function sortNumeric(numArray) {
    numArray.sort(sortNumber);
    return numArray.join(",");
}

function loadGeoJson(id, uri, cache, callback) {
    if (!cache[id]) {
        cache[id] = $.getJSON(uri + '/' + id + '').promise();
    }
    cache[id].done(callback);
}

function loadDataset(uri, callback) {
    if (!dataset.id) {
        dataset = $.getJSON(uri).promise();
    }
    dataset.done(callback);
}
