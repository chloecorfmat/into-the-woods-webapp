/* SCROLL MANAGEMENT */
function preventDefault(e) {
    e = e || window.event;
    if (e.preventDefault)
        e.preventDefault();
    e.returnValue = false;
}

function preventDefaultForScrollKeys(e) {
    // left: 37, up: 38, right: 39, down: 40,
    // spacebar: 32, pageup: 33, pagedown: 34, end: 35, home: 36
    if ({37: 1, 38: 1, 39: 1, 40: 1}[e.keyCode]) {
        preventDefault(e);
        return false;
    }
}

function disableScroll() {
    if (window.addEventListener) // older FF
        window.addEventListener('DOMMouseScroll', preventDefault, false);
    window.onwheel = preventDefault; // modern standard
    window.onmousewheel = document.onmousewheel = preventDefault; // older browsers, IE
    window.ontouchmove  = preventDefault; // mobile
    document.onkeydown  = preventDefaultForScrollKeys;
}

function enableScroll() {
    if (window.removeEventListener)
        window.removeEventListener('DOMMouseScroll', preventDefault, false);
    window.onmousewheel = document.onmousewheel = null;
    window.onwheel = null;
    window.ontouchmove = null;
    document.onkeydown = null;
}
function disableDropdown(event) {
    if (!event.target.matches('.dropbtn')) {
        enableScroll();
        var dropdowns = document.getElementsByClassName("dropdown-content");
        for (var i = 0; i < dropdowns.length; i++) {
            var openDropdown = dropdowns[i];
            if (openDropdown.classList.contains('show')) {
                openDropdown.classList.remove('show');
            }
        }
    }
}

function convertRem(value) {
    return value * getRootElementFontSize();
}

function getRootElementFontSize() {
    // Returns a number
    return parseFloat(
        // of the computed font-size, so in px
        getComputedStyle(
            // for the root <html> element
            document.documentElement
        ).fontSize
    );
}

function checkoutForConflict(){
  let keepThis = this;
  let xhr_object = new XMLHttpRequest();
  xhr_object.open('GET', '/editor/raid/'+raidID+'/lastEdit', true);
  xhr_object.send(null);
  xhr_object.onreadystatechange = function () {
    if (this.readyState === XMLHttpRequest.DONE) {
      if (xhr_object.status === 200) {
        let lastEdition = JSON.parse(xhr_object.responseText);
          if(lastEdition.lastEditor != false){
            let getDuration = function(d1, d2) {
            let d3 = new Date(d2 - d1);
            let d0 = new Date(0);
            return {
              getHours: function(){
                return d3.getHours() - d0.getHours();
                },
              getMinutes: function(){
                return d3.getMinutes() - d0.getMinutes();
                },
              getSeconds: function() {
                return d3.getSeconds() - d0.getSeconds();
                },
              toString: function(){
                return (this.getHours() != 0 ?  this.getHours()+ "h "  :"") +
                (this.getMinutes() != 0 ?  this.getMinutes()+ "min "  :"") +
                this.getSeconds()+"s ";
                },
              };
            }
            let date = new Date(Date.parse(lastEdition.lastEdition.date));
            document.getElementById("errorMessage").innerHTML = "Attention "+lastEdition.lastEditor+" a modifié ce raid il y a "+getDuration(date, new Date()).toString()+"  !  <button>X</button>";
            document.getElementById('errorMessage').querySelector('button').addEventListener('click', function(e){
              document.getElementById('errorMessage').style.display = "none";
            });
           }
         }
    }
  }
}

if (typeof(document.getElementById("editorContainer")) !== "undefined" && document.getElementById("editorContainer") !== null) {
  let UID = {
    _current: 0,
    getNew: function () {
      this._current++;
      return this._current;
    }
  };

  HTMLElement.prototype.pseudoStyle = function (element, prop, value) {
    let _this = this;
    let _sheetId = 'pseudoStyles';
    let _head = document.head || document.getElementsByTagName('head')[0];
    let _sheet = document.getElementById(_sheetId) || document.createElement('style');
    _sheet.id = _sheetId;
    let className = 'pseudoStyle' + UID.getNew();

    _this.className += ' ' + className;

    _sheet.innerHTML += ' .' + className + ':' + element + '{' + prop + ':' + value + '}';
    _head.appendChild(_sheet);
    return this
  };

  // Close the dropdown menu if the user clicks outside of it
  window.onclick = disableDropdown;

  document.querySelector('.bar').addEventListener('transitionend', function () {
    mapManager.map.invalidateSize();
  });

  document.getElementById('btn--laterale-bar').addEventListener('click', function () {
    let tab = this.parentElement.parentElement;
    tab.classList.toggle('bar--invisible');
  });


  function checkoutForConflict(){
    var keepThis = this;
    var xhr_object = new XMLHttpRequest();
    xhr_object.open('GET', '/editor/raid/'+raidID+'/lastEdit', true);
    xhr_object.send(null);
    xhr_object.onreadystatechange = function () {
      if (this.readyState === XMLHttpRequest.DONE) {
        if (xhr_object.status === 200) {
          var lastEdition = JSON.parse(xhr_object.responseText);
          //console.log(lastEdition);
          if(lastEdition.lastEditor != false){
            var getDuration = function(d1, d2) {
              d3 = new Date(d2 - d1);
              d0 = new Date(0);
              return {
                getHours: function(){
                  return d3.getHours() - d0.getHours();
                },
                getMinutes: function(){
                  return d3.getMinutes() - d0.getMinutes();
                },
                getSeconds: function() {
                  return d3.getSeconds() - d0.getSeconds();
                },
                toString: function(){
                  return (this.getHours() != 0 ?  this.getHours()+ "h "  :"") +
                    (this.getMinutes() != 0 ?  this.getMinutes()+ "min "  :"") +
                    this.getSeconds()+"s ";
                },
              };
            }

            var date = new Date(Date.parse(lastEdition.lastEdition.date));
            console.log(new Date(date));
            console.log(new Date())
            document.getElementById("errorMessage").innerHTML = "Attention "+lastEdition.lastEditor+" a modifié ce raid il y a "+getDuration(date, new Date()).toString()+"  !  <button>X</button>";

            document.getElementById('errorMessage').querySelector('button').addEventListener('click', function(e){
              document.getElementById('errorMessage').style.display = "none";
            });
          }
        }
      }
    }

  }
  window.addEventListener('load', function () {

    checkoutForConflict();
    // LOAD EDITOR SPECIFIC CONTROLLER ON MAP
    L.POIEditControl = L.Control.extend({
      options: {
        position: 'topright'
      },
      initialize: function (options) {
        L.Util.setOptions(this, options);
        // Continue initializing the control plugin here.
      },
      onAdd: function (map) {
        let controlElementTag = 'div';
        let controlElementClass = 'my-leaflet-control';
        let controlElement = L.DomUtil.create(controlElementTag, controlElementClass);
        controlElement.innerHTML =
          '<div class="map-controller-container" >' +
          '<span class="switch-label">Déplacer les points d\'intérêt</span>' +
          '<label class="switch">' +
          '<input type="checkbox">' +
          '<span class="slider round"></span>' +
          '</label>' +
          '</div>';
        // Continue implementing the control here.
        controlElement.querySelector("input[type='checkbox']").addEventListener('change',function(){
          mapManager.setPoiEditable(this.checked);
        });
        return controlElement;
      }

    });


    //LOAD EDITOR SPECIFIC CONTROLLER ON MAP
    L.TrackEditControl = L.Control.extend({
      options: {
        position: 'topright'
      },
      initialize: function (options) {
        L.Util.setOptions(this, options);
        // Continue initializing the control plugin here.
      },
      onAdd: function () {
        let controlElementTag = 'div';
        let controlElementClass = 'my-leaflet-control';
        let controlElement = L.DomUtil.create(controlElementTag, controlElementClass);
        controlElement.innerHTML =
          '<div class="map-controller-container" >' +
          '<span class="switch-label">Édition du parcours</span>' +
          '<button class="btn-leave-track-edit">' +
          'X'+
          '</button>' +
          '</div>';
        // Continue implementing the control here.
        controlElement.querySelector(".btn-leave-track-edit").addEventListener('click',function(e){
          e.preventDefault();
          e.stopImmediatePropagation();
          track = mapManager.tracksMap.get(mapManager.currentEditID);
          if (track.line.editor.drawing() ) {
            track.line.editor.pop();
          }
          mapManager.switchMode(EditorMode.READING);
          this.checked = true;
          mapManager.displayTrackButton(false);
        });
        return controlElement;
      }
    });

    let ImportGPXCtrl = L.Control.extend({
      options: {
        position: 'topleft'
      },
      onAdd: function(map) {
        let container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
        container.style.backgroundColor = 'white';
        container.style.width = '30px';
        container.style.height = '30px';
        container.innerHTML = "<i class=\"fas fa-file-import fa-2x\"></i>";
        container.setAttribute("title", "Importer un fichier GPX");
        container.onclick = function(e) {
          e.preventDefault();
          mapManager.editorUI.cleanImportGPXPopin();
          MicroModal.show('import-gpx');
        }
        return container;
      },
    });

    let ExportGPXCtrl = L.Control.extend({
      options: {
        position: 'topleft'
      },
      onAdd: function(map) {
        let container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
        container.style.backgroundColor = 'white';
        container.style.width = '30px';
        container.style.height = '30px';
        container.innerHTML = "<i class=\"fas fa-file-export fa-2x\"></i>";
        container.setAttribute("title", "Exporter un fichier GPX");
        container.onclick = function(e) {
          e.preventDefault();
          MicroModal.show('export-gpx');
        };
        return container;
      },
    });


    let ignTileUrl = "http://wxs.ign.fr/" + IGNAPIKEY
        + "/geoportail/wmts?SERVICE=WMTS&REQUEST=GetTile&VERSION=1.0.0&"
        + "LAYER=GEOGRAPHICALGRIDSYSTEMS.MAPS&STYLE=normal&TILEMATRIXSET=PM&"
        + "TILEMATRIX={z}&TILEROW={y}&TILECOL={x}&FORMAT=image%2Fjpeg";

    let ignTiles = L.tileLayer(
        ignTileUrl,
        {attribution: '&copy; <a href="http://www.ign.fr/">IGN</a>'}
    );

    ignTiles.addTo(mapManager.map);
    L.control.layers({"OpenStreetMap":mapManager.OSMTiles, "IGN":ignTiles}).addTo(mapManager.map);

    mapManager.map.addControl(new ImportGPXCtrl());
    mapManager.map.addControl(new ExportGPXCtrl());

    mapManager.trackControl = new L.TrackEditControl();
    MapManager.prototype.displayTrackButton = function (b) {
      b ? mapManager.map.addControl(mapManager.trackControl) :  mapManager.map.removeControl(mapManager.trackControl);
    }
    mapManager.map.addControl(new L.POIEditControl());

    let acc = document.getElementsByClassName("accordion");

    for (let i = 0; i < acc.length; i++) {
      acc[i].addEventListener("click", function() {
        this.classList.toggle("active");
        let panel = this.nextElementSibling;
        if (panel.style.maxHeight){
          panel.style.maxHeight = null;
        } else {
          panel.style.maxHeight = panel.scrollHeight + "px";
        }
      });
      acc[i].nextElementSibling.style.maxHeight = acc[i].nextElementSibling.scrollHeight +"px";
    }
  });

  document.getElementById('fabActionButton').addEventListener('click', function (e) {
    if (this.classList.contains('add--poi')) {
      if(mapManager.mode == EditorMode.ADD_POI) {
        if (mapManager.waitingPoi != null) {
          mapManager.map.removeEventListener("mousemove");
          mapManager.map.removeLayer(mapManager.waitingPoi.marker);
        }
      }else if(mapManager.mode == EditorMode.TRACK_EDIT){
        e.preventDefault();
        e.stopImmediatePropagation();
        mapManager.tracksMap.get(mapManager.currentEditID).push();
      }
      mapManager.switchMode(mapManager.lastMode);
      this.classList.remove('add--poi')
    }
  });

  document.getElementById('addPoiButton').addEventListener('click', function () {
    let fabActionButton = document.getElementById("fabActionButton");
   // fabActionButton.classList.toggle('add--poi');
//    if (fabActionButton.classList.contains('add--poi')) {
      mapManager.switchMode(EditorMode.ADD_POI);
    //} else {
   //   mapManager.switchMode(mapManager.lastMode);
   // }
  });

  document.getElementById('addTrackButton').addEventListener('click', function () {
    MicroModal.show('add-track-popin');
  });

  document.getElementById('addTrack_form').addEventListener('submit', function (e) {
    e.preventDefault();
    let trName = document.getElementById('addTrack_name').value;
    let trColor = document.getElementById('addTrack_color').value;
    let trSport = document.getElementById('addTrack_sportType').value;
    mapManager.requestNewTrack(trName, trColor, trSport);
    MicroModal.close('add-track-popin');

    document.getElementById('addTrack_name').value = '';
    document.getElementById('addTrack_color').value = '#000000'
  });

  document.getElementById('editTrack_form').addEventListener('submit', function (e) {
    e.preventDefault();
    let trName = document.getElementById('editTrack_name').value;
    let trColor = document.getElementById('editTrack_color').value;
    let trId = document.getElementById('editTrack_id').value;
    let trSport = document.getElementById('editTrack_sportType').value;

    let track = mapManager.tracksMap.get(parseInt(trId));

    track.setName(trName);
    track.setColor(trColor);
    track.setSportType(trSport);

    track.push();
    MicroModal.close('edit-track-popin');
  });

  document.getElementById('editTrack_delete').addEventListener('click', function () {
    MicroModal.close('edit-track-popin');
  });

  // DELETE TRACK
  document.getElementById('btn--delete-track').addEventListener('click', function () {
    let trId = parseInt(this.dataset.id);
    let track = mapManager.tracksMap.get(parseInt(trId));
    track.remove();
    MicroModal.close('delete-track');

    document.getElementById('track-name-delete').value = '';
    document.getElementById('track-name-delete').dataset.name = '';
  });

  document.getElementById('btn--delete-poi').addEventListener('click', function () {
    let poiId = this.dataset.id;
    let poi = mapManager.poiMap.get(parseInt(poiId));
    poi.remove();
    MicroModal.close('delete-poi');
  });

// ADD POI SUBMIT
  document.getElementById('addPoi_form').addEventListener('submit', function (e) {
    e.preventDefault();
    let poiName = document.getElementById('addPoi_name').value;
    let poiType = document.getElementById('addPoi_type').value;
    let poiHelpersCount = document.getElementById('addPoi_nbhelper').value;

    MicroModal.close('add-poi-popin');
    mapManager.requestNewPoi(poiName, poiType, poiHelpersCount);

    document.getElementById('addPoi_name').value = '';
    document.getElementById('addPoi_type').value = '';
    document.getElementById('addPoi_nbhelper').value = '';
  });

// EDIT POI SUBMIT
  document.getElementById('editPoi_form').addEventListener('submit', function (e) {
    e.preventDefault();
    let poiId = document.getElementById('editPoi_id').value;
    let poi = mapManager.poiMap.get(parseInt(poiId));

    poi.name = document.getElementById('editPoi_name').value;
    poi.poiType = mapManager.poiTypesMap.get(parseInt(document.querySelector('#editPoi_type').value));
    poi.requiredHelpers = parseInt(document.getElementById('editPoi_nbhelper').value);
    poi.push();

    MicroModal.close('edit-poi-popin');

    document.getElementById('editPoi_name').value = '';
    document.getElementById('editPoi_type').value = '';
    document.getElementById('editPoi_nbhelper').value = '';
  });

  //Import GPX
  document.getElementById("import-gpx--input").addEventListener("change", function () {
    let file = this.files[0];
    mapManager.GPXImporter.openGPX(file);
  });

  document.getElementById("import-gpx--form").addEventListener("submit", function (e) {
    e.preventDefault();

    let boxes = this.querySelectorAll('#import-gpx--tracks input[type=checkbox]');
    for (let trackBox of boxes) {
      if(trackBox.checked){
        let sportType = trackBox.parentNode.querySelector('select').value;
        mapManager.GPXImporter.importTrack(parseInt(trackBox.dataset.id), sportType);
      }
    }

    let routeBoxes = this.querySelectorAll('#import-gpx--routes input[type=checkbox]');
    for (let rteBox of routeBoxes) {
      if(rteBox.checked){
        let sportType = rteBox.parentNode.querySelector('select').value;
        mapManager.GPXImporter.importRoute(parseInt(rteBox.dataset.id), sportType);
      }
    }

    let waypointBoxes = this.querySelectorAll('#import-gpx--waypoints input[type=checkbox]');
    for (let box of waypointBoxes) {
      if(box.checked){
        let poiType = box.parentNode.querySelector('select').value;
        mapManager.GPXImporter.importWaypoint(parseInt(box.dataset.id), poiType);
      }
    }

    MicroModal.close('import-gpx');
  });

  //Export GPX
  document.getElementById('export-gpx--track').addEventListener('click', function () {
    mapManager.GPXExporter.exportAsTracks();
    MicroModal.close('export-gpx');
  });

  //Export GPX
  document.getElementById('export-gpx--route').addEventListener('click', function () {
    mapManager.GPXExporter.exportAsRoutes();
    MicroModal.close('export-gpx');
  });

  console.log("Editor JS loaded");

  MapManager.prototype.displayTrackButton = function () {
  }
};