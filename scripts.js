var file
file = "https://raw.githubusercontent.com/Noverdose/Preventanyl-Server/develop/canada/admin_level_2.geojson"
file = "https://raw.githubusercontent.com/Noverdose/Preventanyl-Server/develop/canada/admin_level_8.geojson"
function getFile(fn){
    console.log('before')
    $.get(fn, function(data) {    
        var lines = data.split("\n");

        $.each(lines, function(n, elem) {
            try {
                let json = JSON.parse(elem)
                if (typeof json['name'] !== 'undefined' && typeof json['geometry']['coordinates'][0] !== 'undefined' && json["osm_type"] == "way") {
                    addLocation(json['name'], json['geometry']['coordinates'][0])
                }
            } catch (e) {}
        });
    });    
    console.log('after')
}

function addLocation(name, coordinates) {
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

getFile(file)
