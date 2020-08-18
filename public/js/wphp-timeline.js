/*
 * A module for creating the timeline visualizations for WPHP.
 *
 */

const baseuri = window.location.href.split('#')[0];
const titleTable = document.getElementById('titleRoles');
let initJson = {};
let timelineJson = {};
let storymapJson = {};
let wikiResults = {};
let vizDiv = document.getElementById('viz');



/* Set up the mutation observer in order to add specific styling
 * on load; timeline has no callback, so it has to be done this way */
var observer = new MutationObserver(function (mutations, me) {
    var canvas = document.querySelectorAll('.tl-timegroup')[0];
    if (canvas) {
        addStylingToTimeline();
        removeArbitraryWidth();
        me.disconnect();
        // stop observing
        return;
    }
});

function hasGeo(){
    for (let title in initJson['contributions']){
        console.log(title);
        if (initJson['contributions'][title].hasOwnProperty('lat')){
            return true;
        }
    }
    
}
/* Fetch the timeline */
fetch(baseuri + '/timeline')
.then(response => response.json())
.then(data => {
    initJson = data;
    if (Object.keys(initJson['contributions']).length > 0 && document.getElementById('timeline')){
        console.log('???');
        makeTimelineJson();
        new TL.Timeline('timeline', timelineJson);   
    }
    if (hasGeo()){
        makeMap();
 
    }
    
    
    
    
    
    

});

function makeMap(){
        const svg =  "<svg height='100' width='100'><circle cx='50' cy='50' r='40' stroke='black' stroke-width='3' fill='red' /></svg>";
        let iconDataUrl = 'data:image/svg+xml;base64,' + btoa(svg);
        console.log(iconDataUrl);
        let newIcon = L.icon({
            iconUrl: iconDataUrl
        });
        let map = L.map('map').setView([51.505, -0.09], 3);
        L.tileLayer('https://stamen-tiles.a.ssl.fastly.net/toner-lite/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'}
            ).addTo(map);  
        let places = {};
        for (title in initJson['contributions']){
            let currTitle = initJson['contributions'][title];
            if (currTitle.hasOwnProperty('lat')){
                // Check the object
                let loc = currTitle['locationOfPrinting'];
                console.log(loc);
                console.log(places.hasOwnProperty(loc));
                if (places.hasOwnProperty(loc)){
                    var i = places[loc].pop();
                    places[loc].push(i+1);
                    
                } else {
                    places[loc] = [parseFloat(currTitle.lat), parseFloat(currTitle.lon), 1]
                }
            }   
            
        }
        

        for (let place in places){
            let currPlace = places[place];
            let instances = places[place].pop();
            console.log(places[place]);
            console.log(instances);
            marker = new L.marker(places[place]).addTo(map).bindPopup(place + ": " + instances.toString()).openPopup();
        }
       
        
}


/* 
 * Creates the timeline JSON object
 * 
 */
function makeTimelineJson() {
    
    /* If the the timeline JSON object hasn't already been made... */
    if (Object.keys(timelineJson).length === 0) {
        
        /* Set up some variables */
        let person = initJson[ 'person'];
        let contributions = initJson[ 'contributions'];
        let birthArr = returnDate(person, 'dob');
        let deathArr = returnDate(person, 'dod');
        
        
        /* And initialize some of the arrays */
        timelineJson.events =[];
        timelineJson.title = {};
        timelineJson.title.text = {};
        
        /* Start making stuff  */
        
         /* Create the headline, which is the person's first and last name */
        timelineJson.title.text.headline = person.firstName + " " + person.lastName;
        
        /* Now add the Wikipedia URL if it's an English Wikipedia URL */
        if (person.hasOwnProperty('wikipediaUrl') && person['wikipediaUrl'].match(/^https?:\/\/en\.wiki/g)){
            timelineJson.title.media = {};
            timelineJson.title.media.url = person.wikipediaUrl;
        }
        
        /* Now create the titles bit */
        for (let c in contributions) {
            let titleObj = contributions[c];
            let thisEventArr =[];
            if (titleObj.hasOwnProperty('pubDate') && titleObj[ 'pubDate'].length > 0 && titleObj[ 'pubDate'].match(/^\d+$/g)) {
                thisEventArr.start_date = {
                };
                let pubArr = returnDate(titleObj, 'pubDate');
                constructDateObj(thisEventArr.start_date, pubArr);
                let genre = getGenre(titleObj);
                thisEventArr.group = genre[0];
                thisEventArr.text = {};
                thisEventArr.text.headline = ` <span data-genre="${genre[1]}">${titleObj[ 'title']}</span>`;
                if ((deathArr !== null)) {
                    let age = ageDiff(birthArr, returnDate(titleObj, 'pubDate'));
                    let posthumous = pubArr[0] > deathArr[0];
                    let subtitleText;
                    if (posthumous) {
                        subtitleText = "Published posthumously (" + age + ").";
                    } else {
                        subtitleText = age;
                    }
                    thisEventArr.text.text = subtitleText + " " + `<a href="${baseuri.split('person')[0] + 'title/' + c}"> View Record </a>`;
                }
                timelineJson.events.push(thisEventArr);
            }
        }
        
        timelineJson.title.text.text = "Author of " + timelineJson.events.length + " titles";
        
        /* And add birth and date date events if we can */
        
        let milestones =[ 'dob', 'dod'];
        for (let milestone in milestones) {
            let prop = milestones[milestone];
            if (person.hasOwnProperty(prop)) {
                thisEventArr =[];
                thisEventArr.start_date = {};
                thisEventArr.text = {};
                constructDateObj(thisEventArr.start_date, returnDate(person, prop));
                let headlineText = (prop == 'dod') ? 'Died': 'Born';
                let cityProp = (prop == 'dod') ? 'cityOfDeath': 'cityOfBirth';
                
                if (prop == 'dod') {
                    headlineText += " " + ageDiff(birthArr, deathArr) + ""
                };
                
                if (person.hasOwnProperty(cityProp)) {
                    headlineText += " in " + person[cityProp];
                }
                
                thisEventArr.text.headline = headlineText;
                timelineJson.events.push(thisEventArr);
            }
        }
    }
}



/*
 * Function to override the arbitrary width of the slide content (it's set to something
 * like 525px, but it can just be 100%)
 *
 */
function removeArbitraryWidth() {
    let slideTexts = document.querySelectorAll('.tl-slide-content');
    slideTexts.forEach(function (t) {
        t.style.width = '100%';
    });
}


/*
 * This moves the normalized genre up to the closest tl-timemarker
 * (We could probably make this a bit nicer...
 *
 *
 */
function addStylingToTimeline() {
    let groups = document.querySelectorAll(".tl-timegroup");
    let genreSpans = document.querySelectorAll("span[data-genre");
    genreSpans.forEach(function (s) {
        let genre = s.getAttribute('data-genre');
        let el = s.closest('.tl-timemarker');
        if (el) {
            el.setAttribute('data-genre', genre);
        }
    });
    console.log(groups);
    console.log(groups.length);
    if (groups.length > 12){
        document.getElementById('timeline').style.height= "2000px";
    }
}



/* ====== UTILITIES ===== */ 

/* 
 * Returns the genre from the object
 * @param obj {object} the object that may contain the genre property
 * 
 */
function getGenre(obj) {
    let genreStr = (obj.hasOwnProperty('genre')) ? capitalize(obj[ 'genre']): 'Unknown';
    let genreNorm = genreStr.replace(/[\s-:,\.\/]*/g, '');
    return new Array(genreStr, genreNorm);
}

/*
 * Function to create the date object for the timeline JS
 * from a date object
 *
 *
 */
function constructDateObj(source, dateArray) {
    for (var i = 0; i < 3; i++) {
        let val = dateArray[i];
        if (val == 0) {
            return;
        }
        if (i == 0) {
            source.year = val;
        } else if (i == 1) {
            source.month = val;
        } else {
            source.day = val;
        }
    }
}

/*
 * Get the difference between two dates
 * and renders the difference as an age string (d2 - d1)
 *
 * d1 {array}
 * d2 {array}
 */
function ageDiff(d1, d2) {
    let granularity;
    let precision = true;
    let diff = d2[0] - d1[0];
    
      /* Now determine the granularity: if both dates have the same
     * field that's not 0, then that's the best we can do */
    for (let i = 0; i < 3; i++) {
        if (d1[i] > 0 && (d2[i] > 0)) {
            granularity = i;
        }
    }
    
    /* If the granularity is 0 (i.e. a year),
     * then we just know that the precision is false */
    if (granularity == 0) {
        precision = false;
    } else
    /* If the granularity is at the month level, then we have a chance at
     * knowing whether or not we have a precise age */
    if (granularity == 1) {
        
        /* If it's the same month, then we just don't know what their age is */
        if ((d2[1] - d1[1]) == 0) {
            precision = false;
        } else {
            /* Otherwise, we can figure it out */
            if ((d2[1] - d1[1]) < 0) {
                diff = diff -1;
            }
        }
    } else {
        if (!(d2[1] - d1[1] > 0 || (d2[1] == d1[1] && d2[2] > d1[2]))) {
            precision = false;
        }
    }
    
    
    if (precision) {
        return "Age: " + diff;
    } else {
        return "Appromiate age: " + diff;
    }
}


function returnDate(arr, key) {
    return (arr.hasOwnProperty(key)) ? arr[key].split('-').map(x => parseInt(x)): null;
}




/*
 * Utility function to capitalize a string
 */
function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
};



// start observing
observer.observe(document, {
    childList: true,
    subtree: true
});