var db = firebase.database()
var file = "https://raw.githubusercontent.com/Aw3someOne/Preventanyl-Server/firebase/samplekits.json"

function loadJSON(fn) {
    $.get(fn, function(data) {
        let json = JSON.parse(data)
        db.ref('/statickits/').set(json)
    })
}

function addStaticKit(id, params) {
    db.ref('/statickits/' + id).set({
        
    })
}

// db.ref('/statickits/').set(null)

loadJSON(file)
