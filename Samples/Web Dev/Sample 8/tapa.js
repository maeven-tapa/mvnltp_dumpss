// API key placeholder and city na gusto natin kunin for weather
const apiKey = "74504242fc2771098af78cc2ba4e2701";
const city = "Dasmarinas,PH";

async function getWeather() {
    try {
        // According to reference we need this URL papunta sa OpenWeather API gamit ang city at API key
        let url = `https://api.openweathermap.org/data/2.5/weather?q=${city}&units=metric&appid=${apiKey}`;
        let response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Kinokonvert po ang result into JSON para magamit natin sa JS
        let data = await response.json();
        
        // Show current weather
        let weatherDiv = document.getElementById("current-weather");
        let icon = data.weather[0].icon;
        weatherDiv.innerHTML = `
            <img src="https://openweathermap.org/img/wn/${icon}@2x.png" alt="weather icon">
            <h2>${data.main.temp}°C</h2>
            <p>${data.weather[0].description}</p>
            <p>Feels like: ${data.main.feels_like}°C</p>
        `;

        // To call the forecast function para makuha yung mga weather data
        getForecast();
        
        // Then I define a line for updating the map location
        updateMap(data.coord.lat, data.coord.lon);

    } catch (error) {
        console.error("Error:", error);
        document.getElementById("current-weather").innerHTML = `
            <p>Error loading weather. Please check:</p>
            <ul>
                <li>Your internet connection</li>
                <li>API key is valid</li>
                <li>City name is correct</li>
            </ul>
        `;
    }
}

// Function for the forecast (5 data points or days lang para simple)
async function getForecast() {
    try {
        let url = `https://api.openweathermap.org/data/2.5/forecast?q=${city}&units=metric&appid=${apiKey}`;
        let response = await fetch(url);
        let data = await response.json();
        
        let forecastDiv = document.getElementById("hourly-forecast");
        forecastDiv.innerHTML = "";
        
        // Loop po para kunin ang 5 forecast data
        for (let i = 0; i < 5; i++) {
            let forecast = data.list[i];
            let date = new Date(forecast.dt * 1000);
            let time = date.toLocaleTimeString('en-US', {hour: '2-digit', minute:'2-digit'});
            
            let box = document.createElement("div");
            box.classList.add("forecast-box");
            box.innerHTML = `
                <p>${time}</p>
                <img src="https://openweathermap.org/img/wn/${forecast.weather[0].icon}.png" alt="icon">
                <p>${Math.round(forecast.main.temp)}°C</p>
            `;
            forecastDiv.appendChild(box);
        }
    } catch (error) {
        console.error("Forecast error:", error);
        document.getElementById("hourly-forecast").innerHTML = "<p>Forecast not available</p>";
    }
}

// 🗺 Simple map setup to create map of Dasmarinas
let map;
function initMap() {
    map = L.map("map").setView([14.3294, 120.9360], 15);
    
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png").addTo(map);
    
    L.marker([14.3294, 120.9360]).addTo(map)
        .bindPopup("Dasmariñas, Cavite")
        .openPopup();
}

// Function for map update kapag may bagong coordinates
function updateMap(lat, lon) {
    // to zoom the map
    map.setView([lat, lon], 15);

    // remove existing markers
    map.eachLayer(function(layer) {
        if (layer instanceof L.Marker) {
            map.removeLayer(layer);
        }
    });
    // for adding new marker
    L.marker([lat, lon]).addTo(map)
        .bindPopup("Dasmariñas, Cavite")
        .openPopup();
}

// To start everything when page loads.
window.onload = function() {
    initMap();
    getWeather();
};
