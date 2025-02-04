from PyQt6.QtWidgets import QApplication, QWidget, QVBoxLayout, QPushButton, QSlider
from PyQt6.QtMultimedia import QMediaPlayer, QAudioOutput
from PyQt6.QtMultimediaWidgets import QVideoWidget
from PyQt6.QtCore import QUrl, Qt
import sys

class MediaPlayerExample(QWidget):
    def __init__(self):
        super().__init__()
        self.setWindowTitle("QMediaPlayer Example")
        self.setGeometry(100, 100, 600, 400)
        
        # Initialize QMediaPlayer and QAudioOutput
        self.player = QMediaPlayer()
        self.audio_output = QAudioOutput()
        self.player.setAudioOutput(self.audio_output)
        
        # Create video widget
        self.video_widget = QVideoWidget()
        self.player.setVideoOutput(self.video_widget)
        
        # Load a sample media file (Make sure the file exists in the directory)
        self.player.setSource(QUrl.fromLocalFile("samplevid.mp4"))
        
        # UI Elements
        layout = QVBoxLayout()
        layout.addWidget(self.video_widget)
        
        self.play_button = QPushButton("Play")
        self.play_button.clicked.connect(self.player.play)
        layout.addWidget(self.play_button)
        
        self.pause_button = QPushButton("Pause")
        self.pause_button.clicked.connect(self.player.pause)
        layout.addWidget(self.pause_button)
        
        self.stop_button = QPushButton("Stop")
        self.stop_button.clicked.connect(self.player.stop)
        layout.addWidget(self.stop_button)
        
        self.volume_slider = QSlider(Qt.Orientation.Horizontal)
        self.volume_slider.setRange(0, 100)
        self.volume_slider.setValue(50)
        self.volume_slider.valueChanged.connect(self.audio_output.setVolume)
        layout.addWidget(self.volume_slider)
        
        self.setLayout(layout)
        self.show()
        
if __name__ == "__main__":
    app = QApplication(sys.argv)
    window = MediaPlayerExample()
    sys.exit(app.exec())
