<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
    :root {
        --glow-rgb: 239 42 201;
    }

    body {
        background: linear-gradient(145deg, rgb(119, 46, 195), rgb(58, 18, 153));
        height: 100vh;
        overflow: hidden;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    .glow-point {
        position: absolute;
        box-shadow: 0rem 0rem 1.2rem 0.6rem rgb(var(--glow-rgb));
        pointer-events: none;
    }

    .star {
        position: absolute;
        z-index: 2;
        color: white;
        font-size: 1rem;
        animation-duration: 1500ms;
        animation-fill-mode: forwards;
        pointer-events: none;
    }

    @keyframes fall-1 {
        0% {
            transform: translate(0px, 0px) rotateX(45deg) rotateY(30deg) rotateZ(0deg) scale(0.25);
            opacity: 0;
        }

        5% {
            transform: translate(10px, -10px) rotateX(45deg) rotateY(30deg) rotateZ(0deg) scale(1);
            opacity: 1;
        }

        100% {
            transform: translate(25px, 200px) rotateX(180deg) rotateY(270deg) rotateZ(90deg) scale(1);
            opacity: 0;
        }
    }

    @keyframes fall-2 {
        0% {
            transform: translate(0px, 0px) rotateX(-20deg) rotateY(10deg) scale(0.25);
            opacity: 0;
        }

        10% {
            transform: translate(-10px, -5px) rotateX(-20deg) rotateY(10deg) scale(1);
            opacity: 1;
        }

        100% {
            transform: translate(-10px, 160px) rotateX(-90deg) rotateY(45deg) scale(0.25);
            opacity: 0;
        }
    }

    @keyframes fall-3 {
        0% {
            transform: translate(0px, 0px) rotateX(0deg) rotateY(45deg) scale(0.5);
            opacity: 0;
        }

        15% {
            transform: translate(7px, 5px) rotateX(0deg) rotateY(45deg) scale(1);
            opacity: 1;
        }

        100% {
            transform: translate(20px, 120px) rotateX(-180deg) rotateY(-90deg) scale(0.5);
            opacity: 0;
        }
    }
</style>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Real Time Switch</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="antialiased font-sans">

    <livewire:toggle />
    <script>
        let start = new Date().getTime();

        const originPosition = {
            x: 0,
            y: 0
        };

        const last = {
            starTimestamp: start,
            starPosition: originPosition,
            mousePosition: originPosition
        }

        const config = {
            starAnimationDuration: 1500,
            minimumTimeBetweenStars: 250,
            minimumDistanceBetweenStars: 75,
            glowDuration: 75,
            maximumGlowPointSpacing: 10,
            colors: ["249 146 253", "252 254 255"],
            sizes: ["1.4rem", "1rem", "0.6rem"],
            animations: ["fall-1", "fall-2", "fall-3"]
        }

        let count = 0;

        const rand = (min, max) => Math.floor(Math.random() * (max - min + 1)) + min,
            selectRandom = items => items[rand(0, items.length - 1)];

        const withUnit = (value, unit) => `${value}${unit}`,
            px = value => withUnit(value, "px"),
            ms = value => withUnit(value, "ms");

        const calcDistance = (a, b) => {
            const diffX = b.x - a.x,
                diffY = b.y - a.y;

            return Math.sqrt(Math.pow(diffX, 2) + Math.pow(diffY, 2));
        }

        const calcElapsedTime = (start, end) => end - start;

        const appendElement = element => document.body.appendChild(element),
            removeElement = (element, delay) => setTimeout(() => document.body.removeChild(element), delay);

        const createStar = position => {
            const star = document.createElement("span"),
                color = selectRandom(config.colors);

            star.className = "star fa-solid fa-star";

            star.style.left = px(position.x);
            star.style.top = px(position.y);
            star.style.fontSize = selectRandom(config.sizes);
            star.style.color = `rgb(${color})`;
            star.style.textShadow = `0px 0px 1.5rem rgb(${color} / 0.5)`;
            star.style.animationName = config.animations[count++ % 3];
            star.style.starAnimationDuration = ms(config.starAnimationDuration);

            appendElement(star);

            removeElement(star, config.starAnimationDuration);
        }

        const createGlowPoint = position => {
            const glow = document.createElement("div");

            glow.className = "glow-point";

            glow.style.left = px(position.x);
            glow.style.top = px(position.y);

            appendElement(glow)

            removeElement(glow, config.glowDuration);
        }

        const determinePointQuantity = distance => Math.max(
            Math.floor(distance / config.maximumGlowPointSpacing),
            1
        );

        /* --

        The following is an explanation for the "createGlow" function below:

        I didn't cover this in my video, but I ran into an issue where moving the mouse really quickly caused gaps in the glow effect. Kind of like this:

        *   *       *       *    *      *    🖱️

        instead of:

        *************************************🖱️

        To solve this I sort of "backfilled" some additional glow points by evenly spacing them in between the current point and the last one. I found this approach to be more visually pleasing than one glow point spanning the whole gap.

        The "quantity" of points is based on the config property "maximumGlowPointSpacing".

        My best explanation for why this is happening is due to the mousemove event only firing every so often. I also don't think this fix was totally necessary, but it annoyed me that it was happening so I took on the challenge of trying to fix it.

        -- */
        const createGlow = (last, current) => {
            const distance = calcDistance(last, current),
                quantity = determinePointQuantity(distance);

            const dx = (current.x - last.x) / quantity,
                dy = (current.y - last.y) / quantity;

            Array.from(Array(quantity)).forEach((_, index) => {
                const x = last.x + dx * index,
                    y = last.y + dy * index;

                createGlowPoint({
                    x,
                    y
                });
            });
        }

        const updateLastStar = position => {
            last.starTimestamp = new Date().getTime();

            last.starPosition = position;
        }

        const updateLastMousePosition = position => last.mousePosition = position;

        const adjustLastMousePosition = position => {
            if (last.mousePosition.x === 0 && last.mousePosition.y === 0) {
                last.mousePosition = position;
            }
        };

        const handleOnMove = e => {
            const mousePosition = {
                x: e.clientX,
                y: e.clientY
            }

            adjustLastMousePosition(mousePosition);

            const now = new Date().getTime(),
                hasMovedFarEnough = calcDistance(last.starPosition, mousePosition) >= config
                .minimumDistanceBetweenStars,
                hasBeenLongEnough = calcElapsedTime(last.starTimestamp, now) > config.minimumTimeBetweenStars;

            if (hasMovedFarEnough || hasBeenLongEnough) {
                createStar(mousePosition);

                updateLastStar(mousePosition);
            }

            createGlow(last.mousePosition, mousePosition);

            updateLastMousePosition(mousePosition);
        }

        window.onmousemove = e => handleOnMove(e);

        window.ontouchmove = e => handleOnMove(e.touches[0]);

        document.body.onmouseleave = () => updateLastMousePosition(originPosition);
    </script>
</body>

</html>
