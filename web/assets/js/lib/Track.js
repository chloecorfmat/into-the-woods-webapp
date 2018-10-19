var Track = function (map) {
    this.map = map;
    this.line = [];
   //s this.line.addTo(this.map);

    this.id = "";
    this.name = "";
    this.color = "";
    this.sportType = 1;

    this.visible = true;
    this.waypoints = [];

    this.line = L.polyline([]);
}

Track.prototype.setName = function(name){
    this.name = name;

    li = document.getElementById("track-li-"+this.id);
    li.querySelector("label > span:nth-child(3)").innerHTML = this.name;
}

Track.prototype.setColor = function(color){
    this.color = color;
    this.line.setStyle({
        color: color
    });

    li = document.getElementById("track-li-"+this.id);
    li.querySelector("label > span.checkmark").style.backgroundColor = this.color;
    li.querySelector("label > span.checkmark").style.borderColor = this.color;
}

Track.prototype.setEditable = function(b){
    b ? this.line.enableEdit() : this.line.disableEdit();
}


Track.prototype.calculDistance = function () {
    var points = this.line.getLatLngs();
    this.distance = 0;
    if (points.length > 1) {
        for (i = 0; i < points.length - 1; i++) {
            this.distance += points[i].distanceTo(points[i + 1]);
        }
    }
};

Track.prototype.hide = function(){
    var points = this.waypoints;
    for (var point in points) {
        this.map.removeLayer(points[point]);
    }
    this.map.removeLayer(this.line);
    this.visible = false;
}

Track.prototype.show = function(){
    var points = this.waypoints;
    for (var point in points) {
        this.map.addLayer(points[point]);
    }
    this.map.addLayer(this.line);
    this.visible = true;
}


Track.prototype.toJSON = function(){
    latlong =  [];
    for(obj of this.line.getLatLngs() ){
        latlong.push({lat : obj.lat, lng : obj.lng } );
    }
    var track =
    {
        id : this.id !=null ? this.id : null,
        name : this.name,
        color : this.color,
        sportType : this.sportType,
        isVisible:  this.visible,
        trackpoints :  this.line != null ? JSON.stringify(latlong) : null
    }
    var json = JSON.stringify(track)
    return json;
}

Track.prototype.fromObj = function(track){

    this.id = track.id;
    this.color = track.color;
    this.name = track.name;
    this.sportType = track.sportType;
    this.isVisible = track.isVisible;
    test = JSON.parse(track.trackpoints);

    this.line = L.polyline(test, {color: this.color}).addTo(this.map);

    this.line.enableEdit();

}
Track.prototype.fromJSON = function(json){
   var track = JSON.parse(json);
   this.fromObj(track);
}

Track.prototype.push = function(){
    var xhr_object = new XMLHttpRequest();
    xhr_object.open("PATCH", "/organizer/raid/"+raidID+"/track/"+this.id, true);
    xhr_object.setRequestHeader("Content-Type","application/json");
    xhr_object.send(this.toJSON());
    //console.log("pushed: "+this.toJSON());

    li = document.getElementById("track-li-"+this.id);
    this.calculDistance();
    li.querySelector("label > span:nth-child(4)").innerHTML = "("+Math.round(10 * this.distance / 1000) / 10 + " Km)";
}

Track.prototype.remove = function(){
    var xhr_object = new XMLHttpRequest();
    xhr_object.open("DELETE", "/organizer/raid/"+raidID+"/track/"+this.id, true);
    xhr_object.setRequestHeader("Content-Type","application/json");
    xhr_object.send(null);

    this.map.removeLayer(this.line);

    li = document.getElementById("track-li-"+this.id);
    document.getElementById('editor--list').removeChild(li);
}


Track.prototype.buildUI = function(li){
    newTrack = this;
    li.id = "track-li-"+newTrack.id;
    li.classList.add("checkbox-item");
    li.innerHTML = `
       <label class="checkbox-item--label">
           <input data-id = "`+newTrack.id+`" type="checkbox" checked="checked">
           <span style ="background-color : `+newTrack.color+`; border-color :`+newTrack.color+`" class="checkmark">
                <i class="fas fa-check"></i>
           </span>
           <span class="trackName-`+newTrack.name+`">`+newTrack.name+`</span>
           <span style="font-size : 0.75rem;"></br>(150,0 km)</span>
       </label>
       <button data-id = "`+newTrack.id+`" class="btn--track--edit">
           <i class="fas fa-pen"></i>
       </button>
       <button data-id = "`+newTrack.id+`" class="btn--track--settings">
           <i class="fas fa-cog"></i>
       </button>`;

    newTrack.calculDistance();
    li.querySelector("label > span:nth-child(4)").innerHTML = "("+Math.round(10 * newTrack.distance / 1000) / 10 + " Km)";

    // TRACK SELECTION LISTENER
    li.querySelectorAll('input').forEach(function(input){
        input.addEventListener('change', function () {
            if(input.checked){
                mapManager.showTrack(parseInt(input.dataset.id));
                li.querySelector('.btn--track--edit').style.display = "inline-block";
                li.querySelector("label > span.checkmark").style.backgroundColor =  li.querySelector("label > span.checkmark").style.borderColor;
            }else{
                li.querySelector("label > span.checkmark").style.backgroundColor = "#ffffff";
                if(mapManager.currentEditID == input.dataset.id){
                    document.querySelectorAll('.track--edit').forEach(function (el) {
                        el.classList.remove('track--edit')
                    })
                    mapManager.switchMode(EditorMode.READING);
                }
                mapManager.hideTrack(parseInt(input.dataset.id))
                li.querySelector('.btn--track--edit').style.display = "none";
            }
        });
    });

    //TRACK EDIT PENCIL
    li.querySelectorAll('.btn--track--edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!this.parentElement.classList.contains('track--edit')) {
                document.querySelectorAll('.track--edit').forEach(function (el) {
                    el.classList.remove('track--edit')
                })
            }
            // console.log(btn);
            mapManager.currentEditID = parseInt(btn.dataset.id) ;
            mapManager.switchMode(EditorMode.TRACK_EDIT);
            this.parentElement.classList.toggle('track--edit');
        })
    });

    //TRACK SETTINGS COG
    li.querySelectorAll('.btn--track--settings').forEach(function (btn) {
        var id = parseInt(btn.dataset.id);
        var track =  mapManager.tracksMap.get(id);

       // console.log(track);

        btn.addEventListener('click', function () {

            document.querySelector('#TrackSettings_name').value  = track.name;
            document.querySelector('#TrackSettings_color').value = track.color;
            document.querySelector('#TrackSettings_id').value    = track.id;

            MicroModal.show('track-setting-popin');

        });
    });
    return li;
}


