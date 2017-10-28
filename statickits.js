var db = firebase.database()

function addStaticKit(id, params) {
    db.ref('/statickits/' + id).set({
        
    })
}

db.ref('/statickits/').set(null)
