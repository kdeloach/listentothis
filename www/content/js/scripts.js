
var ytplayer;
var shiftDown = false;
var SHIFT_KEY = 16;
var volume = 100;
var initialVideo = '';

$(document).ready(function() {
    $(".progressbar").progressbar({ value: 0 });

    $(".progressbar").click(function(evt) {
        updateProgress(evt);
        $('.btnPlay').addClass('hide');
        $('.btnPause').removeClass('hide');
    });

    $(".btnPause").click(function(evt) {
        pause();
        $('.btnPlay').removeClass('hide');
        $('.btnPause').addClass('hide');
        evt.preventDefault();
    });
    $(".btnPlay").click(function(evt) {
        play();
        $('.btnPlay').addClass('hide');
        $('.btnPause').removeClass('hide');
        evt.preventDefault();
    });
    $(".btnVolume").click(function(evt) {
        updateVolumeUI(evt);
        setVolume(volume);
        evt.preventDefault();
    });
    $(".btnHideVideo").click(function(evt) {
        $('#myytplayer').toggleClass('invisible');
        var value = $('#myytplayer').hasClass('invisible') ? 'true' : 'false';
        $.cookie('hidevideo', value, { expires: 365 });
        evt.preventDefault();
    });

    if($.cookie('hidevideo') == 'true') {
        $('#myytplayer').addClass('invisible');
    }
});

$(document).keyup(function(evt) {
    if(evt.keyCode == SHIFT_KEY) {
        shiftDown = false;
    }
});

$(document).keydown(function(evt) {
    if(evt.keyCode == SHIFT_KEY) {
        shiftDown = true;
    }
});

function updateVolumeUI(evt) {
    var btn = evt.currentTarget;
    var result = nextClass(btn, ['vol-low', 'vol-med', 'vol-high']);
    if(result == 'vol-low') {
        volume = 25;
    } else if (result == 'vol-med') {
        volume = 60;
    } else if (result == 'vol-high') {
        volume = 100;
    }
}

function nextClass(obj, classArray) {
    if(shiftDown) {
        classArray = classArray.reverse();
    }
    var allClasses = obj.className.split(' ').reverse();
    for(i in allClasses) {
        var className = allClasses[i];
        var pos = $.inArray(className, classArray);
        if(pos > -1) {
            var result = classArray[ (pos + 1) % classArray.length ];
            obj.className = obj.className.replace(className, result);
            return result;
        }
    }
}

function onYouTubePlayerReady(playerId) {
    ytplayer = document.getElementById("myytplayer");
    setInterval(updateytplayerInfo, 250);
    updateytplayerInfo();
    ytplayer.addEventListener("onStateChange", "onytplayerStateChange");
    ytplayer.addEventListener("onError", "gotoNextSong");
    loadInitialVideo();
}

function onytplayerStateChange(newState) {
    if (newState == 0) {
        gotoNextSong();
    }
}

function gotoNextSong() {
    document.location.href = $('.btnNext').attr('href');
}

function loadInitialVideo() {
    loadNewVideo(initialVideo);
}

function updateytplayerInfo() {
    updateProgressBarUI();
}

function updateProgressBarUI() {
    var currentTime = getCurrentTime();
    var duration = getDuration();
    if(duration <= 0) {
        return;
    }
    var value = currentTime / duration * 100;
    $('.progressbar').progressbar('option', 'value', value);
}

function updateProgress(evt) {
    var bar = $(evt.currentTarget);
    var mouseX = evt.pageX;
    if(Math.min(bar.width(), mouseX) <= 0) {
        return;
    }
    var perc = (mouseX - bar.offset().left) / bar.width();
    var targetSec = getDuration() * perc;
    seekTo(targetSec);
}

function loadNewVideo(id, startSeconds) {
  startSeconds = startSeconds || 0;
  if (ytplayer) {
    ytplayer.loadVideoById(id, parseInt(startSeconds));
  }
}

function cueNewVideo(id, startSeconds) {
    if (ytplayer) {
        ytplayer.cueVideoById(id, startSeconds);
    }
}

function play() {
    if (ytplayer) {
        ytplayer.playVideo();
    }
}

function pause() {
    if (ytplayer) {
        ytplayer.pauseVideo();
    }
}

function stop() {
    if (ytplayer) {
        ytplayer.stopVideo();
    }
}

function getPlayerState() {
    if (ytplayer) {
        return ytplayer.getPlayerState();
    }
}

function seekTo(seconds) {
    if (ytplayer) {
        ytplayer.seekTo(seconds, true);
        updateProgressBarUI();
    }
}

function getBytesLoaded() {
    if (ytplayer) {
        return ytplayer.getVideoBytesLoaded();
    }
}

function getBytesTotal() {
    if (ytplayer) {
        return ytplayer.getVideoBytesTotal();
    }
}

function getCurrentTime() {
    if (ytplayer) {
        return ytplayer.getCurrentTime();
    }
}

function getDuration() {
    if (ytplayer) {
        return ytplayer.getDuration();
    }
}

function getStartBytes() {
    if (ytplayer) {
        return ytplayer.getVideoStartBytes();
    }
}

function mute() {
    if (ytplayer) {
        ytplayer.mute();
    }
}

function unMute() {
    if (ytplayer) {
        ytplayer.unMute();
    }
}

function getEmbedCode() {
    alert(ytplayer.getVideoEmbedCode());
}

function getVideoUrl() {
    alert(ytplayer.getVideoUrl());
}

function setVolume(newVolume) {
    if (ytplayer) {
        ytplayer.setVolume(newVolume);
    }
}

function getVolume() {
    if (ytplayer) {
        return ytplayer.getVolume();
    }
}

function clearVideo() {
    if (ytplayer) {
        ytplayer.clearVideo();
    }
}
