from PySide6.QtWidgets import QApplication, QWidget, QVBoxLayout, QPushButton, QLineEdit
from PySide6.QtWebEngineWidgets import QWebEngineView
from PySide6.QtCore import QUrl
import sys

class WebBrowserExample(QWidget):
    def __init__(self):
        super().__init__()
        self.setWindowTitle("QWebEngineView Example")
        self.setGeometry(100, 100, 800, 600)
        self.browser = QWebEngineView()

        self.browser.setUrl(QUrl("https://www.google.com"))

        layout = QVBoxLayout()
        self.url_bar = QLineEdit()
        self.url_bar.setPlaceholderText("Enter URL and press Enter...")
        self.url_bar.returnPressed.connect(self.load_url)
        layout.addWidget(self.url_bar)
        layout.addWidget(self.browser)
        
        self.reload_button = QPushButton("Reload")
        self.reload_button.clicked.connect(self.browser.reload)
        layout.addWidget(self.reload_button)
        
        self.stop_button = QPushButton("Stop")
        self.stop_button.clicked.connect(self.browser.stop)
        layout.addWidget(self.stop_button)
        
        self.back_button = QPushButton("Back")
        self.back_button.clicked.connect(self.browser.back)
        layout.addWidget(self.back_button)
        
        self.forward_button = QPushButton("Forward")
        self.forward_button.clicked.connect(self.browser.forward)
        layout.addWidget(self.forward_button)
        
        self.setLayout(layout)
        self.browser.urlChanged.connect(self.update_url_bar)
    
    def load_url(self):
        url = self.url_bar.text()
        if not url.startswith("http"):
            url = "https://" + url
        self.browser.setUrl(QUrl(url))
    
    def update_url_bar(self, url):
        self.url_bar.setText(url.toString())

if __name__ == "__main__":
    app = QApplication(sys.argv)
    window = WebBrowserExample()
    window.show()
    sys.exit(app.exec())
