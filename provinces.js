var file
file = "https://raw.githubusercontent.com/UnderratedDev/Preventanyl-Server/develop/canada/admin_level_4.geojson";

function getFile(fn){
    $.get(fn, function(data) {
        data = JSON.parse (data);
        console.log (data);
        // var lines = data.split("\n");

        // $.each(lines, function(n, elem) {
        // $(data).each (function (elem) {
            console.log (data.features.length);
        for (let i = 0; i < data.features.length; ++i) {
            // console.log (elem);
            try {
                if (typeof data.features[i]['name'] !== 'undefined' && typeof data.features[i]['geometry']['coordinates'][0] !== 'undefined' && data.features[i]["osm_type"] !== 'undefined') {
                    // addLocation(json['name'], json['geometry']['coordinates'][0])
                    addLocationV2(data.features[i]['name'], data.features[i]['geometry']['coordinates'][0])
                }
            } catch (e) {
            }
        }
        console.log ("END");
    });
}

function addLocation(name, coordinates) {
    firebase.database().ref('/regions/' + name).set({
        name: name,
    })
    
    for (let i = 0; i < coordinates[0].length; i++) {
        firebase.database().ref('/regions/' + name + '/geometry/coordinates/' + i).set({
            lat: coordinates[0][i][0],
            long: coordinates[0][i][1]
        })
        // console.log(i)
    }
}

function addLocationV2(name, coordinates) {
    /* if (name != "British Columbia") {
        console.log (name);
        return;
    } */

    var c = []
    c['coordinates'] = []
    for (let i = 0; i < coordinates[0].length; i++) {
        var t = []
        t['lat'] = coordinates[0][i][1]
        t['long'] = coordinates[0][i][0]
        c['coordinates'].push(t)
    }

    firebase.database().ref('/regions/' + name).set({
        name: name,
        geometry: c
    })
}

function getFiles () {
    for (let i = 0; i <= 13; ++i) {
        file = "https://raw.githubusercontent.com/Noverdose/Preventanyl-Server/develop/canada/admin_level_" + i + ".geojson";
        getFile (file);
    }
}

firebase.database().ref('/regions/').set(null)
getFile(file)
// getFiles();
