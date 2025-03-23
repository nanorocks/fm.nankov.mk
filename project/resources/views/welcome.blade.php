<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <div class="drawer lg:drawer-open">
        <input id="my-drawer-2" type="checkbox" class="drawer-toggle" />
        <div class="drawer-content flex flex-col">
            <!-- Page content here -->
            <div class="navbar bg-base-100 shadow-sm">
                <div class="flex-none">
                    <label for="my-drawer-2" class="btn btn-square btn-ghost drawer-button lg:hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            class="inline-block h-5 w-5 stroke-current">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16">
                            </path>
                        </svg>
                    </label>
                </div>
                <div class="flex-1">
                    <a class="btn btn-ghost text-lg sm:text-xl">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            class="inline-block h-5 w-5 mr-2 hidden sm:inline-block">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-2v13">
                            </path>
                            <circle cx="6" cy="18" r="3" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2"></circle>
                            <circle cx="18" cy="16" r="3" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2"></circle>
                        </svg>
                        <span class="hidden sm:inline">FM radio stations - Macedonia</span>
                        <span class="inline sm:hidden text-small">FM Macedonia</span>
                    </a>
                </div>
                <div class="flex-none">
                    <button class="btn btn-square btn-ghost">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            class="inline-block h-5 w-5 stroke-current">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z">
                            </path>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="mb-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 p-4 mb-5">
                    @foreach ($stations as $station)
                        <div class="card shadow-sm relative">
                            <figure class="relative tooltip">
                                <img src="{{ $station['photo'] }}" alt="{{ $station['title'] }}"
                                    class="w-full rounded" />
                                <div class="absolute inset-0 bg-black opacity-30 rounded"></div>

                                <button
                                    class="btn btn-success rounded-full absolute inset-0 m-auto w-16 h-16 flex items-center justify-center tooltip play-button"
                                    data-tip="{{ $station['title'] }}" data-audio-url="{{ $station['audio_url'] }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" class="w-8 h-8 play-icon text-white">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 3v18l15-9-15-9z" />
                                    </svg>
                                </button>
                                <button class="absolute top-2 right-2 favorite-button"
                                    data-station-id="{{ $station['id'] }}" data-station-title="{{ $station['title'] }}"
                                    data-audio-url="{{ $station['audio_url'] }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" class="w-6 h-6 favorite-icon">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                                    </svg>
                                </button>
                            </figure>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>


        <div class="drawer-side">
            <label for="my-drawer-2" aria-label="close sidebar" class="drawer-overlay"></label>
            <ul class="menu bg-base-200 text-base-content min-h-full w-80">
                <li class="menu-title text-xl bg-transparent shadow-none border-none">
                    <a href="#" class="flex items-center space-x-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            class="inline-block h-5 w-5 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                        </svg>
                        <span>Favorites</span>
                    </a>
                </li>
                <div id="favorites-list"></div>
            </ul>
        </div>
    </div>

    <div
        style="background-color: hwb(160.71deg 0% 34.12%); position: fixed; bottom: 0; left: 0; width: 100%; z-index: 50;">
        <audio id="audio-player" controls class="w-full text-white m-1">
            <style>
                #audio-player::-webkit-media-controls-panel {
                    background-color: hwb(160.71deg 0% 34.12%);
                    color: #ffffff;
                    border: transparent;
                    border-radius: 0 !important;
                    display: flex;
                    justify-content: center;
                }

                #audio-player::-webkit-media-controls-play-button,
                #audio-player::-webkit-media-controls-pause-button {
                    color: #10b981;
                }

                #audio-player::-webkit-media-controls-current-time-display,
                #audio-player::-webkit-media-controls-time-remaining-display {
                    color: #ffffff;
                }
            </style>
        </audio>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const audioPlayer = document.getElementById('audio-player');
            const playButtons = document.querySelectorAll('.play-button');
            const favoriteButtons = document.querySelectorAll('.favorite-button');
            const favoritesList = document.getElementById('favorites-list');

            function resetPlayIcons() {
                playButtons.forEach(button => {
                    const playIcon = button.querySelector('.play-icon');
                    playIcon.innerHTML =
                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v18l15-9-15-9z" />';
                });

                // Reset play icons in the favorites list
                document.querySelectorAll('.favorite-item').forEach(item => {
                    const playIcon = item.querySelector('.play-icon');
                    if (playIcon) {
                        playIcon.innerHTML =
                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v18l15-9-15-9z" />';
                    }
                });
            }

            function updateFavoritesList() {
                const favorites = JSON.parse(localStorage.getItem('favorites')) || [];
                favoritesList.innerHTML = '';
                favorites.forEach(station => {
                    const li = document.createElement('li');
                    li.classList.add('relative', 'group', 'flex', 'items-center', 'space-x-2', 'p-2',
                        'hover:bg-gray-100', 'rounded');
                    li.innerHTML = `
                        <div class="flex items-center space-x-2 w-full text-xs">
                            <img src="${station.photo}" alt="${station.title}" class="w-8 h-8 rounded-full">
                            <a href="#" class="favorite-item flex-1 menu-title font-light" data-audio-url="${station.audio_url}">
                                ${station.title}
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-4 h-4 play-icon text-gray-500">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v18l15-9-15-9z" />
                                </svg>
                            </a>
                            <button class="delete-favorite" data-station-id="${station.id}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    `;
                    favoritesList.appendChild(li);
                });

                document.querySelectorAll('.delete-favorite').forEach(button => {
                    button.addEventListener('click', function() {
                        const stationId = this.getAttribute('data-station-id');
                        let favorites = JSON.parse(localStorage.getItem('favorites')) || [];
                        favorites = favorites.filter(station => station.id !== stationId);
                        localStorage.setItem('favorites', JSON.stringify(favorites));
                        updateFavoritesList();
                    });
                });

                document.querySelectorAll('.favorite-item').forEach(item => {
                    item.addEventListener('click', function(event) {
                        event.preventDefault();
                        const audioUrl = this.getAttribute('data-audio-url');

                        // Reset all play icons before updating the clicked one
                        resetPlayIcons();

                        if (audioUrl) {
                            audioPlayer.src = audioUrl;
                            audioPlayer.play();

                            // Update the play icon for the clicked item
                            const playIcon = this.querySelector('.play-icon');
                            if (playIcon) {
                                playIcon.innerHTML =
                                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 4h4v16H6zM14 4h4v16h-4z" />';
                            }
                        }
                    });
                });
            }

            function updateFavoriteButtons() {
                const favorites = JSON.parse(localStorage.getItem('favorites')) || [];
                favoriteButtons.forEach(button => {
                    const stationId = button.getAttribute('data-station-id');
                    const isFavorite = favorites.some(station => station.id === stationId);
                    const favoriteIcon = button.querySelector('.favorite-icon');
                    if (isFavorite) {
                        favoriteIcon.innerHTML =
                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />';
                    } else {
                        favoriteIcon.innerHTML =
                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />';
                    }
                });
            }

            playButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const audioUrl = this.getAttribute('data-audio-url');
                    const playIcon = this.querySelector('.play-icon');

                    // Reset all play icons before updating the clicked one
                    resetPlayIcons();

                    if (audioPlayer.src !== audioUrl) {
                        audioPlayer.src = audioUrl;
                        audioPlayer.play();
                        playIcon.innerHTML =
                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 4h4v16H6zM14 4h4v16h-4z" />';
                    } else if (audioPlayer.paused) {
                        audioPlayer.play();
                        playIcon.innerHTML =
                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 4h4v16H6zM14 4h4v16h-4z" />';
                    } else {
                        audioPlayer.pause();
                        playIcon.innerHTML =
                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v18l15-9-15-9z" />';
                    }
                });
            });

            favoriteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const stationId = this.getAttribute('data-station-id');
                    const stationTitle = this.getAttribute('data-station-title');
                    const stationAudioUrl = this.getAttribute('data-audio-url');
                    const stationPhoto = this.closest('.card').querySelector('img').src;
                    let favorites = JSON.parse(localStorage.getItem('favorites')) || [];

                    if (favorites.some(station => station.id === stationId)) {
                        favorites = favorites.filter(station => station.id !== stationId);
                    } else {
                        favorites.push({
                            id: stationId,
                            title: stationTitle,
                            audio_url: stationAudioUrl,
                            photo: stationPhoto
                        });
                    }

                    localStorage.setItem('favorites', JSON.stringify(favorites));
                    updateFavoritesList();
                    updateFavoriteButtons();
                });
            });

            audioPlayer.addEventListener('ended', function() {
                resetPlayIcons();
            });

            updateFavoritesList();
            updateFavoriteButtons();
        });
    </script>
</body>

</html>
