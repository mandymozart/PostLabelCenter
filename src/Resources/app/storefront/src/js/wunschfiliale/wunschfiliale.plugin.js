import Plugin from 'src/plugin-system/plugin.class';
import L from '../leaflet/leaflet';
import HttpClient from 'src/service/http-client.service';

export default class WunschfilialePlugin extends Plugin {
    static options = {
        buttonId: 'choosePostfiliale',
        searchInput: 'searchBranchInput',
        map: 'map',
        branchList: 'branchList',
        branchModalContent: 'branchModalContent',
        noBranchDataFound: 'noBranchDataFound',
        modalBodyClass: '.modal-body',
        mapVisibility: 'mapVisibility',
        searchBranchButton: 'searchBranchButton'
    };

    init() {
        this.mainButton = document.getElementById(this.options.searchBranchButton);
        this.branchKey = (this.mainButton) ? this.mainButton.dataset.branch : null;
        this.searchButton = document.getElementById(this.options.buttonId);
        this.searchInput = document.getElementById(this.options.searchInput);
        this.searchButton.addEventListener('click', this._fetchBranches.bind(this));
        this.searchInput.addEventListener('keypress', this._fetchBranches.bind(this));
        this.httpClient = new HttpClient();
        this.branchList = document.getElementById(this.options.branchList)
        this.branchModalContent = document.getElementById(this.options.branchModalContent)
        this.branchModalContent.style.display = "none";
        this.mapVisibility = document.getElementById(this.options.mapVisibility).value;
    }

    _fetchBranches(event) {
        if (event.type === "click" || (event.type === "keypress" && event.key === "Enter")) {
            this.branchModalContent.style.display = "none";
            if (this.searchInput.value !== "") {
                let noBranchDataDiv = document.getElementById(this.options.noBranchDataFound);
                if (noBranchDataDiv !== null) {
                    noBranchDataDiv.remove();
                }
                this._showSpinner(true);

                let url = '/wunschfiliale/search/' + this.searchInput.value;
                if(this.branchKey !== null){
                    url += ("/" + this.branchKey)
                }

                this.httpClient.get(url, (data, res) => {
                    let response = JSON.parse(data)
                    if (res.status === 200) {
                        if (Boolean(this.mapVisibility) === true) {
                            this._showMapPoints(response.data);
                        } else {
                            this._showOnlyBranches(response.data);
                        }
                    } else {
                        this.branchModalContent.style.display = "none"
                        document.querySelector(this.options.modalBodyClass).insertAdjacentHTML('beforeend', `<div id='noBranchDataFound' class='row mt-3 text-center'><h4>${response.message}</h4></div>`);
                    }
                    this._showSpinner(false);
                });
            }
        }
    }

    // places: fetched data
    _showOnlyBranches(places) {
        // with this variable we will set the location of the map to the last marker and zoom out so that we can see all the markers on the map
        document.getElementById(this.options.branchModalContent).style.display = "flex";
        this.branchList.innerHTML = "";

        places.forEach(place => {
            this._createBranchList(place)
        });

        let buttons = document.querySelectorAll(".selectDeliveryStation")
        for (const button of buttons) {
            button.addEventListener('click', this._changeAddress.bind(this));
        }
    }

    _createBranchList(place) {
        let jsonArray = JSON.stringify(place);
        let openingHours = "";
        if (place.openingHourFormatted !== null) {
            openingHours = "Öffnungszeiten: " + place.openingHourFormatted
        }

        this.branchList.innerHTML += `<div class="col-12 mb-2 singleBranch">
               <div class="branchContent p-3">
                    <div class="branchName"><b>${place.firstLineOfAddress}</b></div>
                    <div class="branchStreet">${place.address.streetName} ${place.address.streetNumber}</div>
                    <div class="branchZipAndPlace">${place.address.postalCode} ${place.address.city}</div>
                    <div class="branchOpenHours">${openingHours}</div>
                    <input type="hidden" name="branchKey${place.branchKey}" value='${jsonArray}'>
              </div><button type="submit" class="pl-3 selectDeliveryStation checkout-link" data-branch-id="${place.branchKey}">${window.wunschfiliale.chooseStation}</button></div>`;
    }

    // places: fetched data
    _showMapPoints(places) {
        this._clearMapContainer();
        this._createMap();
        let location = {};

        // with this variable we will set the location of the map to the last marker and zoom out so that we can see all the markers on the map
        document.getElementById(this.options.branchModalContent).style.display = "flex";
        this.branchList.innerHTML = "";

        places.forEach(place => {
            this._createBranchList(place)

            location = {
                lon: place.address.coordinates.longitudeWGS84,
                lat: place.address.coordinates.latitudeWGS84
            };

            const popupContent = this._generatePopupMarkup(place);
            const marker = L.marker(location);

            marker.bindPopup(popupContent).openPopup();
            marker.addTo(this.map);
        });

        let buttons = document.querySelectorAll(".selectDeliveryStation")
        for (const button of buttons) {
            button.addEventListener('click', this._changeAddress.bind(this));
        }

        this.map.setView(location, 13);

        if (this.invalidateSizeInterval)
            clearInterval(this.invalidateSizeInterval);

        this.invalidateSizeInterval = setInterval(this._invalidateMapSize.bind(this), 500);
    }

    // needed for map to load correctly
    _invalidateMapSize() {
        this.map.invalidateSize();
    }

    _clearMapContainer() {
        if (this.map)
            this.map.remove();
    }

    _generatePopupMarkup(place) {
        let popup = `
            <div class="branchName"><b>${place.firstLineOfAddress}</b></div>
            <div class="branchStreet"> ${place.address.streetName} ${place.address.streetNumber} </div>
            <div class="branchZipAndPlace"> ${place.address.postalCode} ${place.address.city} </div>`;
        if (place.openingHourFormatted !== null) {
            popup += `<div class="branchOpenHours"> ${window.wunschfiliale.openingHours}: ${place.openingHourFormatted} </div>`;
        }

        popup += `<button type="submit" onclick="triggerBranchButton(this)" class="mt-2 selectDeliveryStationIcon checkout-link" data-branch-button="${place.branchKey}">${window.wunschfiliale.chooseStation}</button>`;

        return popup;
    }

    _createMap() {
        this.map = L.map('map');

        L.tileLayer('https://{s}.tile.openstreetmap.de/{z}/{x}/{y}.png', {
            attribution: 'Map <a href="https://memomaps.de/">memomaps.de</a> <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(this.map);
    }

    _showSpinner(show) {
        const spinnerContainer = document.querySelector(".spinner-container");
        if (show) {
            spinnerContainer.style.display = "flex";
        } else {
            spinnerContainer.style.display = "none";
        }
    }

    _changeAddress(element) {
        let branchId = element.target.dataset.branchId;
        let branchElement = document.querySelector("[name=branchKey" + branchId + "]")
        let customerId = document.querySelector("[name=customerId]").value

        if (branchElement !== null) {
            let jsonData = JSON.parse(branchElement.value)
            this.httpClient.post("/wunschfiliale/pickupStation", JSON.stringify({
                "branchData": jsonData,
                "customerId": customerId
            }), (res) => {
                window.location.reload();
            });
        }
    }
}
