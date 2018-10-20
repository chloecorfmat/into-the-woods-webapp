var Poi = function (map) {
    this.map = map;
    this.marker = L.marker([0, 0]);
    this.id = "";
    this.name = "";
    this.poiType = null;
    this.requiredHelpers = 0;

    this.color = "#000000";

}

Poi.prototype.toJSON = function(){
    var poi =
        {
            id : this.id !=null ? this.id : null,
            name : this.name,
            latitude : this.marker.getLatLng().lat,
            longitude : this.marker.getLatLng().lng,
            requiredHelpers : this.requiredHelpers,
            poiType: this.poiType.id
        }
    var json = JSON.stringify(poi);
    console.log(this.requiredHelpers);
    console.log(this.requiredHelpers);
    return json;
}
Poi.prototype.fromObj = function(poi) {
    var keepThis = this;

    // console.log(poi);
    this.id = poi.id;
    this.name = poi.name;
    this.poiType = mapManager.poiTypesMap.get(poi.poiType);
    this.color = this.poiType.color;
    this.requiredHelpers = poi.requiredHelpers;

    this.marker = L.marker([poi.latitude, poi.longitude]);

    this.marker.addTo(this.map);


    this.marker.disableEdit();
    this.marker.on("dragend", function (e) {
        keepThis.push();
    })
    this.li = document.createElement('li');
    this.li.classList.add("list--pois-items");
    this.buildUI();

}
Poi.prototype.fromJSON = function(json){
    var poi = JSON.parse(json);
    this.fromObj(poi);
}
Poi.prototype.setEditable = function (b) {
    b ? this.marker.enableEdit() : this.marker.disableEdit();
}

Poi.prototype.push = function () {
    var xhr_object = new XMLHttpRequest();
    xhr_object.open("PATCH", "/organizer/raid/"+raidID+"/poi/"+this.id, true);
    xhr_object.setRequestHeader("Content-Type","application/json");
    xhr_object.send(this.toJSON());
    console.log(this.toJSON());

}

Poi.prototype.buildUI= function (){
    var keepThis = this;

    const markerHtmlStyles = `
  background-color: ` + this.color + `;
  width: 2rem;
  height: 2rem;
  display: block;
  
  position: relative;
  border-radius: 3rem 3rem 0;
  transform: translateX(-1rem) translateY(-2rem) rotate(45deg);`;
    this.marker.bindPopup('' +
        '<header style="' +
        'background: ' + this.color + ' ;' +
        'color: #ffffff ;' +
        'padding: 0rem 3rem;">' +
        '<h3>' + this.name + '</h3>' +
        '</header>' +
        '<div> ' +
        '<h4>Bénévoles</h4>' +
        '<p>' + this.requiredHelpers + ' Requis </p>' +
        '</div>');
   var icon = L.divIcon({
        className: "my-custom-pin",
        iconAnchor: [0, 5],
        labelAnchor: [0, 0],
        popupAnchor: [0, -35],
        html: `<span style="${markerHtmlStyles}" />`
    });

    this.marker.setIcon(icon);


    this.li.innerHTML = this.name
        + `<button data-id = "` + this.id + `" class="btn--poi--settings">
           <i class="fa fa-cog"></i>
       </button>`;

    document.getElementById("list--pois").appendChild(this.li);
    this.li.pseudoStyle("before", "background-color", this.color);

    this.li.querySelector(".btn--poi--settings").addEventListener("click", function () {

        document.getElementById('editPoi_id').value = keepThis.id;
        document.getElementById('editPoi_name').value = keepThis.name;
        document.getElementById('editPoi_type').value = keepThis.type;
        document.getElementById('editPoi_nbhelper').value = keepThis.requiredHelpers;


        MicroModal.show('edit-poi-popin');
    });
}

Poi.prototype.remove = function(){
    var xhr_object = new XMLHttpRequest();
    xhr_object.open("DELETE", "/organizer/raid/"+raidID+"/poi/"+this.id, true);
    xhr_object.setRequestHeader("Content-Type","application/json");
    xhr_object.send(null);

    this.map.removeLayer(this.marker);

    document.getElementById('list--pois').removeChild(this.li);
}
