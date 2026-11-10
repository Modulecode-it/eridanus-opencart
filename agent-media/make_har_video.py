# Сборка видео-инструкции «Как получить HAR» из кадров agent-media/frames/har-step*.png
# Запуск: python make_har_video.py  (из каталога agent-media)
import os
import numpy as np
import imageio.v2 as iio
from PIL import Image, ImageDraw, ImageFont

BASE = os.path.dirname(os.path.abspath(__file__))
FRAMES = [os.path.join(BASE, "frames", f"har-step{i}.png") for i in range(1, 7)]
OUT_MP4 = os.path.join(BASE, "har-instruction.mp4")
OUT_GIF = os.path.join(BASE, "har-instruction.gif")
W, H = 1280, 800
FPS = 30


def font(name: str, size: int):
    for cand in (name, "segoeui.ttf", "arial.ttf"):
        try:
            return ImageFont.truetype(f"C:/Windows/Fonts/{cand}", size)
        except OSError:
            continue
    return ImageFont.load_default()


def title_card() -> Image.Image:
    img = Image.new("RGB", (W, H), (18, 18, 20))
    d = ImageDraw.Draw(img)
    f1 = font("segoeuib.ttf", 52)
    f2 = font("segoeui.ttf", 24)
    f3 = font("segoeui.ttf", 20)
    t1 = "Как получить HAR-файл"
    t2 = "Инструкция: 6 шагов, примерно полминуты"
    t3 = "eridanus.ru — оформление заказа"
    for t, f, y, c in ((t1, f1, 330, (255, 255, 255)), (t2, f2, 415, (190, 190, 195)), (t3, f3, 470, (120, 170, 220))):
        bb = d.textbbox((0, 0), t, font=f)
        d.text(((W - (bb[2] - bb[0])) / 2, y), t, font=f, fill=c)
    return img


imgs = [title_card()] + [Image.open(p).convert("RGB") for p in FRAMES]
durations = [3.5, 5.0, 5.0, 5.0, 5.5, 5.5, 5.5]  # титул + шаги 1..6 (шаг 5 — контекстное меню, чуть дольше)

# MP4 (H.264, yuv420p — играет везде)
writer = iio.get_writer(OUT_MP4, fps=FPS, codec="libx264", quality=8, pixelformat="yuv420p")
for im, dur in zip(imgs, durations):
    frame = np.asarray(im.resize((W, H)) if im.size != (W, H) else im)
    for _ in range(int(round(dur * FPS))):
        writer.append_data(frame)
writer.close()
print("MP4:", OUT_MP4, os.path.getsize(OUT_MP4), "bytes")

# GIF (запасной вариант: играет даже там, где нет видео-плеера)
gif_frames, gif_durations = [], []
for im, dur in zip(imgs, durations):
    gif_frames.append(im.resize((900, 562)))
    gif_durations.append(int(dur * 1000))
gif_frames[0].save(
    OUT_GIF, save_all=True, append_images=gif_frames[1:],
    duration=gif_durations, loop=0, optimize=True,
)
print("GIF:", OUT_GIF, os.path.getsize(OUT_GIF), "bytes")
