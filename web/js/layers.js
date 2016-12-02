/* Defautlt layers available for the Leiden map */

var osm = L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
});

var cartodb = L.tileLayer('http://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="http://cartodb.com/attributions">CartoDB</a>'
});

/** historical maps */
var deventer1545 = L.esri.tiledMapLayer('http://tiles.arcgis.com/tiles/brgRNnNj6DClnBDf/arcgis/rest/services/1545_van_Deventer/MapServer', {
    'opacity': 0.8,
    maxZoom: 20,
    maxNativeZoom: 19
});
var bast1600 = L.esri.tiledMapLayer('http://tiles.arcgis.com/tiles/brgRNnNj6DClnBDf/arcgis/rest/services/1600_Bast/MapServer', {
    'opacity': 0.8,
    maxZoom: 20,
    maxNativeZoom: 19
});
var blaeu1649 = L.esri.tiledMapLayer('http://tiles.arcgis.com/tiles/brgRNnNj6DClnBDf/arcgis/rest/services/1649_Blaeu/MapServer', {
    'opacity': 0.8,
    maxZoom: 20,
    maxNativeZoom: 19
});
var hagen1670 = L.esri.tiledMapLayer('http://tiles.arcgis.com/tiles/brgRNnNj6DClnBDf/arcgis/rest/services/1670_Hagen/MapServer', {
    'opacity': 0.8,
    maxZoom: 20,
    maxNativeZoom: 19
});
var hagen1675 = L.esri.tiledMapLayer('http://tiles.arcgis.com/tiles/brgRNnNj6DClnBDf/arcgis/rest/services/Kaart_1675_Hagen/MapServer', {
    'opacity': 0.8,
    maxZoom: 20,
    maxNativeZoom: 19
});
var vancampen1850 = L.esri.tiledMapLayer('http://tiles.arcgis.com/tiles/brgRNnNj6DClnBDf/arcgis/rest/services/1850_van_Campen/MapServer', {
    'opacity': 0.8,
    maxZoom: 20,
    maxNativeZoom: 19
});
var vancampen1879 = L.esri.tiledMapLayer('http://tiles.arcgis.com/tiles/brgRNnNj6DClnBDf/arcgis/rest/services/1879_van_Campen/MapServer', {
    'opacity': 0.8,
    maxZoom: 20,
    maxNativeZoom: 19
});
var vancampen1899 = L.esri.tiledMapLayer('http://tiles.arcgis.com/tiles/brgRNnNj6DClnBDf/arcgis/rest/services/Kaart_1899/MapServer', {
    'opacity': 0.8,
    maxZoom: 20,
    maxNativeZoom: 19
});
var ahrend1920 = L.esri.tiledMapLayer('http://tiles.arcgis.com/tiles/brgRNnNj6DClnBDf/arcgis/rest/services/1920_Ahrend/MapServer', {
    'opacity': 0.8,
    maxZoom: 20,
    maxNativeZoom: 19
});
var straetbouc = L.esri.tiledMapLayer('http://tiles.arcgis.com/tiles/brgRNnNj6DClnBDf/arcgis/rest/services/stratengrachtenboek/MapServer', {
    'opacity': 0.8,
    maxZoom: 20,
    maxNativeZoom: 19
});

var mapDefinitions = [
    {
        name: "Van Deventer 1545",
        layer: deventer1545
    },
    {
        name: "Bast 1600",
        layer: bast1600
    },
    {
        name: "Blaeu 1649",
        layer: blaeu1649
    },
    {
        name: "Hagen 1670",
        layer: hagen1670
    },
    {
        name: "Hagen 1675",
        layer: hagen1675
    },
    {
        name: "Van Campen 1850",
        layer: vancampen1850
    },
    {
        name: "Van Campen 1879",
        layer: vancampen1879
    },
    {
        name: "Van Campen 1899",
        layer: vancampen1899
    },
    {
        name: "Ahrend 1920",
        layer: ahrend1920
    },
    {
        name: "Straetbouc",
        layer: straetbouc
    }
];

var baseMaps = [
    {
        name: "Basiskaart",
        layer: cartodb
    },
    {
        group: "Kaarten",
        collapsed: true,
        layers: mapDefinitions
    }
];
