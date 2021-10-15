<?php

require_once("plugins.php");

function hasKey($array, $key) {
  return isset($array[$key]) && $array[$key];
}

function isFlag($flag) {
  return (hasKey($_GET, $flag) ? $_GET[$flag] : (hasKey($_POST, $flag) ? $_POST[$flag] : false));
}

function letUserSetUp($image, $layer) {
  //todo encode params for url?
  header("Location: user_setup.php?image=$image&layer=$layer");
  exit;
}

$errorSource = false;

//can come both from POST and GET
$dataSource = hasKey($_GET, "image") ? $_GET : (hasKey($_POST, "image") ? $_POST : false);
//can come from POST
$visualisation = hasKey($_POST, "visualisation") ? $_POST["visualisation"] : false;

$requireNewSetup = hasKey($_GET, "new") || hasKey($_POST, "new") || hasKey($_COOKIE, "new");

//if no request for new visualisationm try to use cookies
if (!$visualisation && !$requireNewSetup && hasKey($_COOKIE, "visualisation")) {
  $visualisation = $_COOKIE["visualisation"];
  //if data not given in POST/GET, try cookies
  if (!$dataSource) {
    $errorSource = !hasKey($_COOKIE, "image") || !hasKey($_COOKIE, "layer");
    if (!$errorSource) {
      $dataSource = $_COOKIE;
    }
  }
}

//TODO check structure of visualisation object and fail if invalid

if (!$dataSource) { //if missing data, error
  $errorSource = true;
} else { //else visualisation style required
  if ($visualisation) {
    //todo check also POST image and POST layer exist!!!!
    setcookie( "visualisation", $visualisation, strtotime( '+30 days' ) );
    //TODO move there arguments to remain in GET parameters to differentiate between visualisation sources
    setcookie( "image", $dataSource["image"], strtotime( '+30 days' ) );
    setcookie( "layer", $dataSource["layer"], strtotime( '+30 days' ) );
  } else {
    letUserSetUp($dataSource["image"], $dataSource["layer"]);
  }
}

//possible cache
$cached = hasKey($_POST, "cache") && !$requireNewSetup ? $_POST["cache"] : "{}";

foreach ($PLUGINS as $_ => $plugin) {
    $plugin->loaded = !isset($plugin->flag) || !$plugin->flag || isFlag($plugin->flag);
    $plugin->permaLoaded = $plugin->loaded && hasKey($_GET, $plugin->flag);
}


?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
  <meta charset="utf-8">
  <title>Visualisation</title>

  <link rel="stylesheet" href="./style.css">
  <link rel="stylesheet" href="./external/primer_css.css">

  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

  <!-- jquery -->
  <script src="http://code.jquery.com/jquery-1.10.2.min.js"></script>
  
  <!-- OSD -->
  <!-- <script src="./osd/openseadragon.min.js"></script> -->

  <script src="./osd_debug/src/openseadragon.js"></script>

  <script src="./osd_debug/src/eventsource.js"></script>
  <script src="./osd_debug/src/rectangle.js"></script>
  <script src="./osd_debug/src/tile.js"></script>
  <script src="./osd_debug/src/tilecache.js"></script>
  <script src="./osd_debug/src/tiledimage.js"></script>
  <script src="./osd_debug/src/tilesource.js"></script>
  <script src="./osd_debug/src/button.js"></script>
  <script src="./osd_debug/src/buttongroup.js"></script>
  <script src="./osd_debug/src/control.js"></script>
  <script src="./osd_debug/src/controldock.js"></script>
  <script src="./osd_debug/src/displayrectangle.js"></script>
  <script src="./osd_debug/src/drawer.js"></script>
  <script src="./osd_debug/src/dzitilesource.js"></script>
  <script src="./osd_debug/src/fullscreen.js"></script>
  <script src="./osd_debug/src/iiiftilesource.js"></script>
  <script src="./osd_debug/src/imageloader.js"></script>
  <script src="./osd_debug/src/imagetilesource.js"></script>
  <script src="./osd_debug/src/legacytilesource.js"></script>
  <script src="./osd_debug/src/mousetracker.js"></script>
  <script src="./osd_debug/src/viewer.js"></script>
  <script src="./osd_debug/src/navigator.js"></script>
  <script src="./osd_debug/src/osmtilesource.js"></script>
  <script src="./osd_debug/src/overlay.js"></script>
  <script src="./osd_debug/src/placement.js"></script>
  <script src="./osd_debug/src/point.js"></script>
  <script src="./osd_debug/src/profiler.js"></script>
  <script src="./osd_debug/src/referencestrip.js"></script>
  <script src="./osd_debug/src/spring.js"></script>
  <script src="./osd_debug/src/strings.js"></script>
  <script src="./osd_debug/src/tilesourcecollection.js"></script>
  <script src="./osd_debug/src/tmstilesource.js"></script>
  <script src="./osd_debug/src/viewport.js"></script>
  <script src="./osd_debug/src/world.js"></script>
  <script src="./osd_debug/src/zoomifytilesource.js"></script>

  <script src="./webgl/webGLContext.js"></script>
  <script src="./webgl/viaWebGLdynamic.js"></script>
  <script src="./webgl/openSeadragonGLdynamic.js"></script>


  <!--Tutorials-->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/kineticjs/5.2.0/kinetic.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-scrollTo/2.1.2/jquery.scrollTo.min.js"></script>
  <link rel="stylesheet" href="./external/enjoyhint.css">
  <script src="./external/enjoyhint.min.js"></script>

</head>

<body data-color-mode="auto" data-light-theme="light" data-dark-theme="dark_dimmed" >
  <!-- OSD viewer -->
  <div id="viewer-container" class="position-absolute width-full height-full top-0 left-0" style="pointer-events: none;">
     <div id="osd" style="pointer-events: auto;" class="position-absolute width-full height-full top-0 left-0"></div>
  </div>

  <!-- System messaging -->
  <div id="system-message" class="d-none system-container">
    <div id="system-message-warn" class="f00-light text-center"><span class="material-icons f0-light" style="transform: translate(0px, -5px);">error_outline</span>&nbsp;Error</div>
    <div id="system-message-title" class="f2-light text-center clearfix"></div>
    <button id="system-message-details-btn" onclick="$('#system-message-details').css('display', 'block'); $(this).css('visibility', 'hidden');" class="btn" type="button">details</button>
    <div id="system-message-details" class="px-4 py-4 border radius-3 overflow-y-scroll" style="display: none;max-height: 50vh;"></div>
  </div>

  <!--Tutorials-->
  <div id="tutorials-container" class="d-none system-container">
    <div class="f1-light text-center clearfix">Select a tutorial</div>
    <!--<p class="text-center">You can also show tutorial section by pressing 'H' on your keyboard.</p>-->
    <br>
    <div id="tutorials"></div>
    <br><br><button class="btn" onclick="Tutorials.hide();">Exit</button>
  </div>

  <!-- Panel -->
  <span id="main-panel-show" class="material-icons" onclick="$('#main-panel').css('right', 0);">chevron_left</span>

  <div id="main-panel" class="position-fixed d-flex flex-column height-full color-shadow-medium" style="overflow-y: overlay; width: 400px;" data-color-mode="auto" data-light-theme="light" data-dark-theme="dark_dimmed">

  <div id="main-panel-content" class='position-relative' style="padding-bottom: 25px;">
    <span id="main-panel-hide" class="material-icons" onclick="$('#main-panel').css('right', '-400px');">chevron_right</span>
    <div id="navigator-container" class="inner-panel position-absolute right-0 top-0" style="width: 400px;">
      <div id="panel-navigator" class="inner-panel" style=" height: 300px; width: 100%;"></div>
    </div>

    <div id="general-controls" class="inner-panel d-flex" style="margin-top: 320px;">
      <span> Opacity: &emsp;</span>
      <input type="range" id="global-opacity" min="0" max="1" value="1" step="0.1" class="d-flex" style="width: 230px;">&emsp;
    </div> <!--Height of navigator = margn top of this div + padding-->
    <div id="panel-shaders" class="inner-panel" > 

      <!--NOSELECT important due to interaction with slider, default height must be defined due to height adjustment later, TODO: set from cookies-->

      <div class="inner-panel-content noselect" id="inner-panel-content-1">
        <div>

          <span id="shaders-pin" class="material-icons inline-pin" onclick="pinClick($(this), $(this).parents().eq(1).children().eq(1));"> push_pin </span>
          
          <select name="shaders" id="shaders" style="max-width: 88%;" class="form-select v-align-baseline h3 mb-1" aria-label="Visualisation">
            <!--populated with shaders from the list -->
          </select>          
        </div>

        <div id="shader-options" class="inner-panel-hidden">
          <!--populated with options for a given shader -->
        </div>
      </div>
   </div>

   <!-- Appended controls for other plugins -->
  </div>

  <div class="d-flex flex-items-end p-2 flex-1 position-fixed bottom-0" style="width: 400px; background: #0000005c;">
      <span id="global-export" class="pl-2" onclick="exportVisualisation(this);" title="Export visualisation" style="cursor: pointer;"><span class="material-icons">download</span> Export</span>
      <a style="display:none;" id="export-visualisation"></a> &emsp;
      <span id="add-plugins" onclick="addPlugins();" title="Add plugins to the visualisation" style="cursor: pointer;"><span class="material-icons">extension</span> Plugins</span>&emsp;
      <span id="global-help" onclick="Tutorials.show();" title="Show tutorials" style="cursor: pointer;"><span class="material-icons">school</span> Tutorial</span>&emsp;
  </div>
</div>

  <!-- Auto-appended scripts -->
  <div id="auto-scripts"></div>
  

<!-- DEFAULT SETUP SCRIPTING -->
<script type="text/javascript">

   /*---------------------------------------------------------*/
   /*------------ System error messenger ---------------------*/
   /*---------------------------------------------------------*/

  var DisplayError = {
    msgTitle: $("#system-message-title"),
    msgDetails: $("#system-message-details"),
    msgContainer: $("#system-message"), 
    screenContainer: $("#viewer-container"),
      
    show: function(title, description) {
        this.msgTitle.html(title);
        this.msgDetails.html(description);
        this.msgContainer.removeClass("d-none");
        this.screenContainer.addClass("disabled");
    },

    hide: function() {
        this.msgContainer.addClass("d-none");
        this.screenContainer.removeClass("disabled");
    }    
  }   

<?php

if($errorSource) {
  //todo failure, no data source
  echo "$('#main-panel').addClass('d-none');";
  $debugSource = print_r($dataSource, true);
  if (!$debugSource) $debugSource = "null";
  $postdata = print_r($_POST, true);
  echo "DisplayError.show('Something went wrong. Please, re-open the visualizer (your URL might be wrong).', `ERROR: Visualiser expects input data. Following data does not contain one: <br><code>$postdata</code>`);";
  echo "</script></body></html>";
  exit;
} 

?>

   /*---------------------------------------------------------*/
   /*------------ Initialization of Visualisation ------------*/
   /*---------------------------------------------------------*/
  
    var setup = <?php echo $visualisation ?>;  
    var activeShader = 0;

    // Initialize viewer webGL extension - ViaGL
    let shaderNames = $("#shaders");
    seaGL = new openSeadragonGL({
      //todo CHECK if parameters not missing and throw error if required param missing
      htmlControlsId: "shader-options",
      scriptId: "auto-scripts",
      jsGlLoadedCall: "glLoaded",
      jsGlDrawingCall: "glDrawing",
      shaderGenerator: "/iipmooviewer-jiri/OSD2/dynamic_shaders/build.php",

      //called once fully initialized
      ready: function() {
        var i = 0;
        seaGL.foreachVisualisation(function (vis) {
          if (vis.error) {
            shaderNames.append(`<option value="${i}" title="${vis.error}">&#9888; ${vis['name']}</option>`);
          } else {
            shaderNames.append(`<option value="${i}">${vis['name']}</option>`);
          }
          i++;
        });
      },

      //called once a visualisation is compiled and linked (might not happen)
      visualisationReady: function(i, visualisation) {

      },

      //called once a visualisation is switched to (including first run)
      visualisationInUse: function(visualisation) {
        enableDragSort("shader-options");
        //called only if everything is fine
        DisplayError.hide(); //preventive
      },

      //called when visualisation is unable to run
      onFatalError: function(vis) {
        DisplayError.show(vis.error, vis.desc);
      },

      //called when exception (usually some missing function) occurs
      onException: function(error) {
        DisplayError.show("Something went wrong and the visualissation is unable to continue. You can use other visualisation if available.", error.message);
      },

      //called to get custom HTML header for each shader part
      htmlShaderPartHeader: function(title, html, isVisible, isControllable=true) {
        let style = isVisible ? '' : 'style="filter: brightness(0.5);"';
        let checked = isVisible ? 'checked' : '';
        let disabled = isControllable ? '' : 'disabled';
        return `<div class="shader-part rounded-3 mx-1 mb-2 pl-3 pt-1 pb-2" data-id="${title}" ${style}>
            <div class="h5 py-1 position-relative">
              <input type="checkbox" class="form-control" ${checked} ${disabled} data-id="${title}" onchange="shaderPartToogleOnOff(this);">
              &emsp;${title}<span class="material-icons position-absolute right-1" style="width: 10%;">swap_vert</span>
            </div>
            <div class="non-draggable">${html}</div>
            </div>`;
      }
    });

    //Set visualisations
    setup.forEach(visualisationDef => {
      //setup all visualisations defined     
      seaGL.setVisualisation(visualisationDef);  
    });

    //Set cache
    seaGL.viaGL.setCache(<?php echo $cached; ?>);


   /*---------------------------------------------------------*/
   /*------------ Initialization of OpenSeadragon ------------*/
   /*---------------------------------------------------------*/
    var urlImage = "<?php echo $dataSource["image"]; ?>";
    var urlLayer = "<?php echo $dataSource["layer"]; ?>";

    let baseIDX = 0;
    let layerIDX = 1;

    // Initialize viewer - OpenSeadragon
    var viewer = OpenSeadragon({
      id: "osd",
      prefixUrl: "osd/images/",
      showNavigator: true,
      maxZoomPixelRatio: 1,
      showNavigator:  true,
      //navigatorAutoFade:  false,
      showNavigationControl: false,
      navigatorId: "panel-navigator",
      // debugMode:  true,  
    });
    viewer.gestureSettingsMouse.clickToZoom = false;

    /*---------------------------------------------------------*/
    /*------------ JS utilities and enhancements --------------*/
    /*---------------------------------------------------------*/

    //must be defined
    function redraw() {
      seaGL.redraw(viewer.world, layerIDX);
    }

    // Tutorial functionality
    var Tutorials = {
      tutorials: $("#tutorials"), 
      tutContainer: $("#tutorials-container"),
      screenContainer: $("#viewer-container"),
      steps: [],
      prerequisites: [],
    
      show: function() {
          this.tutContainer.removeClass("d-none");
          this.screenContainer.addClass("disabled");
      },

      hide: function() {
          this.tutContainer.addClass("d-none");
          this.screenContainer.removeClass("disabled");
      },

      add: function(name, description, icon, steps, prerequisites=undefined) {
        if (!icon) icon = "school";
        this.tutorials.append(`
          <div class='d-inline-block mx-1 px-2 py-2 pointer v-align-top rounded-2 tutorial-item' onclick="Tutorials.run(${this.steps.length});">
          <span class="d-block material-icons f1 text-center my-2">${icon}</span><p class='f3-light mb-0'>${name}</p><p style='max-width: 150px;'>${description}</div>`);
        this.steps.push(steps);
        this.prerequisites.push(prerequisites);
      },

      run: function(index) {
        if (index >= this.steps.length || index < 0) return;
        $('#main-panel').css('right', '0px');
        //do prerequisite setup if necessary
        if(this.prerequisites[index]) this.prerequisites[index]();

        //reset plugins visibility
        $(".plugins-pin").each(function() {
          let pin = $(this);
          let container = pin.parent().children().eq(2);
          pin.removeClass('pressed');
          pin.removeClass('locked');
          container.removeClass('force-visible');
          container.removeClass('force-hidden');
        });
        let enjoyhintInstance = new EnjoyHint({});
        enjoyhintInstance.set(this.steps[index]);
        this.hide();
        enjoyhintInstance.run();
      }
    }

    Tutorials.add("Basic functionality", "learn how the visualiser works", "foundation", [ {
    'next #viewer-container' : 'You can navigate in the content either using mouse,<br> or via keyboard: arrow keys (movement) and +/- (zoom). Try it out now.'
  },{
    'next #main-panel' : 'On the right, the Main Panel <br> holds most functionality and also allows <br> to interact with plugins.',
  }, {
    'next #navigator-container' : 'An interactive navigator can be used <br> for orientation or to jump quickly on different areas.',
  }, {
    'next #general-controls' : 'These controls allow to affect <br> the whole visualisation, which consists of two layers: <br> the tissue scan and the data layer above.'
  }, {
    'next #global-opacity' : 'You can control the opacity <br> of the data layer here.'
  }, {
    'next #global-export' : 'If you want to share the visualisation, <br> you can export it here - including all <br> active plugins and changes you\'ve made.'
  }, {
    'next #panel-shaders' : 'The data layer <br>-the core visualisation functionality-<br> can be controlled here. Hovering over<br> the element will show additional hidden controls.'
  }, {
    'click #shaders-pin' : 'Click on the pin to set <br>this controls subpanel to be always visible.'
  }, {
    'next #shaders' : 'Multiple different visualisations <br>are supported - you can select <br>which one is being displayed.'
  }, {
    'next #shader-options' : 'Each visualisation consists of several <br>data parts and their interpretation. <br>Here, you can control each part separately, <br>and also drag-n-drop to reorder.'
  }], function() {
    //prerequisite - pin in default state
    let pin = $("#shaders-pin");
    let container = pin.parent().children().eq(1);
    pin.removeClass('pressed');
    pin.removeClass('locked');
    container.removeClass('force-visible');
    container.removeClass('force-hidden');
  });

    // load desired shader upon selection
    $("#shaders").on("change", function () {
      activeShader = $(this).val();
      seaGL.switchVisualisation(activeShader);
      redraw();
    });
    // opacity of general layer available everywhere
    $("#global-opacity").on("input", function () {
      let val = $(this).val();
      viewer.world.getItemAt(layerIDX).setOpacity(val);
    });

    /**
     * From https://github.com/openseadragon/openseadragon/issues/1690
     * brings better zooming behaviour
     */
    viewer.addHandler("canvas-scroll", function (event) {
      if (typeof this.scrollNum == 'undefined') {
        this.scrollNum = 0;
      }

      if (typeof this.lastScroll == 'undefined') {
        this.lastScroll = new Date();
      }

      this.currentScroll = new Date(); //Time that this scroll occurred at

      if (this.currentScroll - this.lastScroll < 400) {
        this.scrollNum++;
      } else {
        this.scrollNum = 0;
        viewer.zoomPerScroll = 1.2;
      }

      if (this.scrollNum > 2 && viewer.zoomPerScroll <= 2.5) { 
        viewer.zoomPerScroll += 0.2;
      }

      this.lastScroll = this.currentScroll; //Set last scroll to now
    });

    document.addEventListener('keydown', (e) => {
      let zoom = null,
          bounds = viewer.viewport.getBounds(),
          speed = 0.3;
			switch (e.key) {
        case "Down": // IE/Edge specific value
        case "ArrowDown":
          bounds.y += speed*bounds.height;
          break;
        case "Up": // IE/Edge specific value
        case "ArrowUp":
          bounds.y -= speed*bounds.height;
          break;
        case "Left": // IE/Edge specific value
        case "ArrowLeft":
          bounds.x -= speed*bounds.width;
          break;
        case "Right": // IE/Edge specific value
        case "ArrowRight":
          bounds.x += speed*bounds.width;
          break;
        case "+":
          zoom = viewer.viewport.getZoom();
          viewer.viewport.zoomTo(zoom + zoom * speed * 3);
          return;
        case "-":
          zoom = viewer.viewport.getZoom();
          viewer.viewport.zoomTo(zoom - zoom * speed * 2);
          return;
        // case "h":
        //   Tutorials.show();
        //   return;
        default:
          return; // Quit when this doesn't handle the key event.
      }
      viewer.viewport.fitBounds(bounds);
		});


   /*---------------------------------------------------------*/
   /*------------ MAIN PANEL JS ------------------------------*/
   /*---------------------------------------------------------*/

    /**
     * Made with love by @fitri
     * This is a component of my ReactJS project https://codepen.io/fitri/full/oWovYj/ 
     * 
     * Shader re-compilation and re-ordering logics
     * Modified by Jiří
     */

    function enableDragSort(listId) {
      const sortableList = document.getElementById(listId);
      Array.prototype.map.call(sortableList.children, (item) => {enableDragItem(item)});
    }

 
    function enableDragItem(item) {
      item.setAttribute('draggable', true);
      item.ondragstart = startDrag;
      item.ondrag = handleDrag;
      item.ondragend = handleDrop;
    }

    function startDrag(event) {
      const currentTarget = event.target;
        let clicked = document.elementFromPoint(event.x, event.y);
        if (isPrevented(clicked, 'non-draggable')) {
          event.preventDefault();
        }
    }
    
    //modified from https://codepen.io/akorzun/pen/aYwXoR
    const isPrevented = (element, cls) => {
      let currentElem = element;
      let isParent = false;
      
      while (currentElem) {
        const hasClass = Array.from(currentElem.classList).some(elem => {return cls === elem;});
        if (hasClass) {
          isParent = true;
          currentElem = undefined;
        } else {
          currentElem = currentElem.parentElement;
        }
      }
      return isParent;
    }

    function handleDrag(item) {
      const selectedItem = item.target,
            list = selectedItem.parentNode,
            x = event.clientX,
            y = event.clientY;
      
      selectedItem.classList.add('drag-sort-active');
      let swapItem = document.elementFromPoint(x, y) === null ? selectedItem : document.elementFromPoint(x, y);
      
      if (list === swapItem.parentNode) {
        swapItem = swapItem !== selectedItem.nextSibling ? swapItem : swapItem.nextSibling;
        list.insertBefore(selectedItem, swapItem);
      }
    }

    function handleDrop(item) {
      item.target.classList.remove('drag-sort-active');
      const listItems = item.target.parentNode.children;

      var order = [];
      Array.prototype.forEach.call(listItems, function(child) {
        order.push(child.dataset.id);
      });

      seaGL.reorder(order);
      redraw();
    }

    function shaderPartToogleOnOff(self) {
      if (self.checked == true) {
        seaGL.currentVisualisation().responseData[self.dataset.id].visible = 1;
        self.parentNode.parentNode.classList.remove("shader-part-error");
      } else {
        seaGL.currentVisualisation().responseData[self.dataset.id].visible = 0;
        self.parentNode.parentNode.classList.add("shader-part-error");
      }
      seaGL.reorder();
      redraw();
    }


    function pinClick(jQSelf, jQTargetParent) {
      if (jQTargetParent.hasClass('force-visible')) {
        jQTargetParent.removeClass('force-visible');
        jQTargetParent.addClass('force-hidden');
        jQSelf.removeClass('pressed');
        jQSelf.addClass('locked')
      } else if (jQTargetParent.hasClass('force-hidden')) {
        jQTargetParent.removeClass('force-hidden');
        jQSelf.removeClass('locked')
      } else {
        jQSelf.addClass('pressed');
        jQTargetParent.addClass('force-visible');
      }
    }


   /*---------------------------------------------------------*/
   /*------------ DIALOGS ------------------------------------*/
   /*---------------------------------------------------------*/

   var Dialogs = {
      MSG_INFO: { class: "", icon: '<path fill-rule="evenodd"d="M6.3 5.69a.942.942 0 0 1-.28-.7c0-.28.09-.52.28-.7.19-.18.42-.28.7-.28.28 0 .52.09.7.28.18.19.28.42.28.7 0 .28-.09.52-.28.7a1 1 0 0 1-.7.3c-.28 0-.52-.11-.7-.3zM8 7.99c-.02-.25-.11-.48-.31-.69-.2-.19-.42-.3-.69-.31H6c-.27.02-.48.13-.69.31-.2.2-.3.44-.31.69h1v3c.02.27.11.5.31.69.2.2.42.31.69.31h1c.27 0 .48-.11.69-.31.2-.19.3-.42.31-.69H8V7.98v.01zM7 2.3c-3.14 0-5.7 2.54-5.7 5.68 0 3.14 2.56 5.7 5.7 5.7s5.7-2.55 5.7-5.7c0-3.15-2.56-5.69-5.7-5.69v.01zM7 .98c3.86 0 7 3.14 7 7s-3.14 7-7 7-7-3.12-7-7 3.14-7 7-7z"/>' },
      MSG_WARN: { class: "Toast--warning", icon: '<path fill-rule="evenodd" d="M8.893 1.5c-.183-.31-.52-.5-.887-.5s-.703.19-.886.5L.138 13.499a.98.98 0 0 0 0 1.001c.193.31.53.501.886.501h13.964c.367 0 .704-.19.877-.5a1.03 1.03 0 0 0 .01-1.002L8.893 1.5zm.133 11.497H6.987v-2.003h2.039v2.003zm0-3.004H6.987V5.987h2.039v4.006z" />' },
      MSG_ERR: { class: "Toast--error", icon: '<path fill-rule="evenodd" d="M10 1H4L0 5v6l4 4h6l4-4V5l-4-4zm3 9.5L9.5 14h-5L1 10.5v-5L4.5 2h5L13 5.5v5zM6 4h2v5H6V4zm0 6h2v2H6v-2z" />' },
      _timer: null,

      init: function() {
        $("body").append(`<div id="annotation-messages-container" class="Toast popUpHide position-fixed" style='z-index: 5050; transform: translate(calc(50vw - 50%));'>
          <span class="Toast-icon"><svg width="12" height="16"v id="annotation-icon" viewBox="0 0 12 16" class="octicon octicon-check" aria-hidden="true"></svg></span>
          <span id="annotation-messages" class="Toast-content v-align-middle"></span>
          <button class="Toast-dismissButton" onclick="Dialogs.hide(false);">
          <svg width="12" height="16" viewBox="0 0 12 16" class="octicon octicon-x" aria-hidden="true"><path fill-rule="evenodd" d="M7.48 8l3.75 3.75-1.48 1.48L6 9.48l-3.75 3.75-1.48-1.48L4.52 8 .77 4.25l1.48-1.48L6 6.52l3.75-3.75 1.48 1.48L7.48 8z"/></svg>
          </button>
          </div>`);

        this._body = $("#annotation-messages-container");
        this._board = $("#annotation-messages");
        this._icon = $("#annotation-icon");
      },

      show: function (text, delayMS, importance) {
        this._board.html(text);
        this._icon.html(importance.icon);
        this._body.removeClass(); //all
        this._body.addClass(`Toast position-fixed ${importance.class}`)
        this._body.removeClass("popUpHide");
        this._body.addClass("popUpEnter");

        if (delayMS > 1000) {
          this._timer = setTimeout(this.hide.bind(this), delayMS);
        }
      },

      hide: function (_autoCalled = true) {
        console.log("remove", this._body)
        this._body.removeClass("popUpEnter");
        this._body.addClass("popUpHide");

        if (!_autoCalled) {
          clearTimeout(this._timer);
        }
        this._timer = null;
      },

      showCustom: function(parentId, title, content, footer) {
        if (!parentId) {
          console.error("Invalid form: unique container id not defined.");
          return;
        }
        $(`#${parentId}`).remove(); //prevent from multiple same windows shown
        $("body").append(`<div id="${parentId}" class="position-fixed" style="z-index:999; left: 50%;top: 50%;transform: translate(-50%,-50%);">
<details-dialog class="Box Box--overlay d-flex flex-column" style=" max-width:80vw; max-height: 80vh">
    <div class="Box-header">
      <button class="Box-btn-octicon btn-octicon float-right" type="button" aria-label="Close help" onclick="$(this).parent().parent().parent().remove();">
        <svg class="octicon octicon-x" viewBox="0 0 12 16" version="1.1" width="12" height="16" aria-hidden="true"><path fill-rule="evenodd" d="M7.48 8l3.75 3.75-1.48 1.48L6 9.48l-3.75 3.75-1.48-1.48L4.52 8 .77 4.25l1.48-1.48L6 6.52l3.75-3.75 1.48 1.48L7.48 8z"></path></svg>
      </button>
      <h3 class="Box-title">${title}</h3>
    </div>
    <div class="overflow-auto position-relative">
      <div class="Box-body overflow-auto" style="padding-bottom: 45px;">
	  ${content}
	  </div>
	  ${footer}
    </div>
  </details-dialog>
  </div>`);
      }
    }  // end of namespace Dialogs
    Dialogs.init();

   /*---------------------------------------------------------*/
   /*------------ EXPORTING ----------------------------------*/
   /*---------------------------------------------------------*/

    function constructExportVisualisationForm(customAttributes="") {
      let visCache = JSON.stringify(seaGL.viaGL.getCache());
      let form = `
      <form method="POST" id="redirect" action="<?php echo "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>">
        <input type="hidden" id="visualisation" name="visualisation">
        <input type="hidden" id="image" name="image">
        <input type="hidden" id="layer" name="layer">
        <input type="hidden" id="cache" name="cache">
        ${customAttributes}
        <input type="submit" value="">
      </form>
      <script type="text/javascript">
        //safely set values (JSON)
        document.getElementById("visualisation").value = '<?php echo $visualisation ?>';
        document.getElementById("image").value = '<?php echo $dataSource["image"]; ?>';
        document.getElementById("layer").value = '<?php echo $dataSource["layer"]; ?>';
        document.getElementById("cache").value = '${visCache}';

        var form = document.getElementById("redirect");
        var node;`;

      //todo add all flags, but only if present in POST.... can be done via PHP
      for (let i = 0; i < PLUGINS._exportHandlers.length; i++) {
        let toExport = PLUGINS._exportHandlers[i];
        if (toExport) {
          let value = toExport.call();
          form += `node = document.createElement("input");
          node.setAttribute("type", "hidden");
          node.setAttribute("name", '${toExport.name}');
          node.setAttribute("value", '${value}');
          form.appendChild(node);`;
        }
      }

      return `${form} 
      form.submit();<\/script>`;
    }

    function exportVisualisation() {
      let doc = `<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
</head>
<body>
${constructExportVisualisationForm()}
</body></html>`;
			let output = new Blob([doc], { type: 'text/html' });
			let downloadURL = window.URL.createObjectURL(output);
      var downloader = document.getElementById("export-visualisation");
			downloader.href = downloadURL;
      downloader.download = "export.html";
      downloader.click();
    }

    /*---------------------------------------------------------*/
   /*------------ PLUGINS ------------------------------------*/
   /*---------------------------------------------------------*/

    var PLUGINS = {
      osd: viewer,
      seaGL: seaGL,
      addTutorial: Tutorials.add.bind(Tutorials),
      dialog: Dialogs,
      appendToMainMenu: function(title, titleHtml, html, id) {
        $("#main-panel-content").append(`<div id="${id}" class="inner-panel"><div><h3 class="d-inline-block h3" style="padding-left: 35px;">${title}&emsp;</h3>${titleHtml}</div><div>${html}</div></div>`);
      },
      appendToMainMenuExtended: function(title, titleHtml, html, hiddenHtml, id) {
        $("#main-panel-content").append(`<div id="${id}" class="inner-panel"><div>
        <span class="material-icons inline-pin plugins-pin" id="${id}-pin" onclick="pinClick($(this), $(this).parent().parent().children().eq(2));"> push_pin </span>
        <h3 class="d-inline-block h3">${title}&emsp;</h3>${titleHtml}
        </div>
        <div>	
        ${html}
        </div><div class='inner-panel-hidden'>${hiddenHtml}</div></div>`);
      },
      appendToMainMenuRaw: function(html, id) {
        $("#main-panel-content").append(`<div id="${id}" class="inner-panel">${html}</div>`);
      },
      addPostExport: function(name, valueHandler) {
        this._exportHandlers.push({name: name, call: valueHandler});
      },
      postData: <?php echo json_encode($_POST)?>,
      each: <?php echo json_encode((object)$PLUGINS)?>,
      _exportHandlers: []
    };

    viewer.addHandler('open', function() {
      PLUGINS.imageLayer = viewer.world.getItemAt(baseIDX);
      PLUGINS.dataLayer = viewer.world.getItemAt(layerIDX);
    });

    function addPlugins() {
      let content = "";
      Object.values(PLUGINS.each).forEach(plugin => {
        let checked = plugin.loaded ? "checked" : "";
        let disabled = plugin.permaLoaded ? "disabled" : "";
        content += `<input class="form-control" id="select-plugin-${plugin.flag}" type="checkbox" value="${plugin.flag}" ${checked} ${disabled}>&emsp;<label for="select-plugin-${plugin.flag}">${plugin.name}</label><br>`;
      });

      Dialogs.showCustom("load-plugins", "Add plugins", content + "<br>", 
      `<button onclick="loadWithPlugins();" class="btn position-absolute bottom-2 right-4">Load with selected</button>`);
    }

    function loadWithPlugins() {
      let formData = "";
      Object.values(PLUGINS.each).forEach(plugin => {
        if (document.getElementById(`select-plugin-${plugin.flag}`).checked) {
          formData += `<input type="hidden" name="${plugin.flag}" value="1">`;
        }
      });
      $("body").append(constructExportVisualisationForm(formData));
    }

  </script>

    <!-- PLUGINS -->
<?php

foreach ($PLUGINS as $_ => $plugin) {
  if ($plugin->loaded) {
    //add plugin style sheet if exists
    if (file_exists(PLUGIN_FOLDER . $plugin->directory . "/style.css")) {
      echo "<link rel=\"stylesheet\" href=\"" . PLUGIN_FOLDER . $plugin->directory . "/style.css\">";
    }
    //add plugin includes
    foreach ($plugin->includes as $_ => $file) {
      echo "<script src=\"" . PLUGIN_FOLDER . $plugin->directory . "/$file\"></script>";
    }
  }
}
?>

  <script type="text/javascript">

    /*---------------------------------------------------------*/
    /*------------ Init                          --------------*/
    /*---------------------------------------------------------*/

    seaGL.loadShaders(function() {
      viewer.open(["/iipsrv/iipsrv.fcgi?Deepzoom=" + urlImage + ".dzi", "/iipsrv/iipsrv.fcgi?Deepzoom=" + urlLayer + ".dzi"]);
    });
    seaGL.init(viewer);

    
    viewer.addHandler('open-failed', function(e) {
      //todo handle cases where image is not loaded properly
      alert("Open failed");
    });
  </script>
</body>
</html>