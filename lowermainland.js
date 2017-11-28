var file
function getFile(fn){
    $.get(fn, function(data) {
        var lines = data.split("\n");

        $.each(lines, function(n, elem) {
            try {
                let json = JSON.parse(elem)
                console.log (elem);
                if (typeof json['name'] !== 'undefined' && typeof json['geometry']['coordinates'][0] !== 'undefined' && json["osm_type"] !== 'undefined') {
                    // addLocation(json['name'], json['geometry']['coordinates'][0])
                    addLocationV2(json['name'], json['geometry']['coordinates'][0])
                }
            } catch (e) {}
        });
    });
}

function addLocation(name, coordinates) {
    // console.log (name);
    // if (name == "Vancouver") {
       // console.log (name);
    // }
    firebase.database().ref('/locations/' + name).set({
        name: name,
    })
    for (let i = 0; i < coordinates[0].length; i++) {
        firebase.database().ref('/locations/' + name + '/geometry/coordinates/' + i).set({
            lat: coordinates[0][i][0],
            long: coordinates[0][i][1]
        })
        // console.log(i)
    }
}

function addLocationV2(name, coordinates) {
    // console.log (name);
    if (name == "Vancouver") {
        console.log (name);
    }
    var c = []
    c['coordinates'] = []
    for (let i = 0; i < coordinates[0].length; i++) {
        var t = []
        t['lat'] = coordinates[0][i][1]
        t['long'] = coordinates[0][i][0]
        c['coordinates'].push(t)
    }
    firebase.database().ref('/locations/' + name).set({
        name: name,
        geometry: c
    })
}

function getFiles () {
    /* for (let i = 0; i <= 13; ++i) {
        file = "https://raw.githubusercontent.com/Noverdose/Preventanyl-Server/develop/canada/admin_level_" + i + ".geojson";
        getFile (file);
    } */
    file = "https://raw.githubusercontent.com/UnderratedDev/Preventanyl-Server/develop/canada/admin_level_8.geojson";
    getFile (file);
}

firebase.database().ref('/locations/').set(null)
// getFile(file)
getFiles();
