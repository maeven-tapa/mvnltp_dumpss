from PySide6.QtWidgets import QApplication, QWidget, QVBoxLayout, QPushButton, QLineEdit, QFileDialog
from PySide6.QtWebEngineWidgets import QWebEngineView
from PySide6.QtCore import QUrl
import sys

class WebBrowserExample(QWidget):
    def __init__(self):
        super().__init__()
        self.setWindowTitle("Python")
        self.setGeometry(100, 100, 800, 600)
        self.browser = QWebEngineView()

        self.browser.setUrl(QUrl("https://www.google.com"))

        layout = QVBoxLayout()
        self.url_bar = QLineEdit()
        self.url_bar.setPlaceholderText("Enter URL and press Enter...")
        self.url_bar.returnPressed.connect(self.load_url)
        layout.addWidget(self.url_bar)
        layout.addWidget(self.browser)
        
        self.back_button = QPushButton("Back")
        self.back_button.clicked.connect(self.browser.back)
        layout.addWidget(self.back_button)
        
        # New feature buttons
        self.print_button = QPushButton("Print to PDF")
        self.print_button.clicked.connect(self.print_to_pdf)
        layout.addWidget(self.print_button)
        
        self.set_html_button = QPushButton("Set Custom HTML")
        self.set_html_button.clicked.connect(self.set_custom_html)
        layout.addWidget(self.set_html_button)
        
        self.zoom_in_button = QPushButton("Zoom In")
        self.zoom_in_button.clicked.connect(self.zoom_in)
        layout.addWidget(self.zoom_in_button)
        
        self.zoom_out_button = QPushButton("Zoom Out")
        self.zoom_out_button.clicked.connect(self.zoom_out)
        layout.addWidget(self.zoom_out_button)
        
        self.trigger_action_button = QPushButton("Trigger Page Action (Refresh)")
        self.trigger_action_button.clicked.connect(self.trigger_page_action)
        layout.addWidget(self.trigger_action_button)
        
        self.setLayout(layout)
        self.browser.urlChanged.connect(self.update_url_bar)
        
        # Initialize zoom factor
        self.current_zoom = 1.0
    
    def load_url(self):
        url = self.url_bar.text()
        if not url.startswith("http"):
            url = "https://" + url
        self.browser.setUrl(QUrl(url))
    
    def update_url_bar(self, url):
        self.url_bar.setText(url.toString())
    
    def print_to_pdf(self):
        file_name, _ = QFileDialog.getSaveFileName(self, "Save PDF", "", "PDF files (*.pdf)")
        if file_name:
            self.browser.page().printToPdf(file_name)
    
    def set_custom_html(self):
        custom_html = """
        <html>
            <head>
                <title>CPET8 - SAMPLE</title>
            </head>
            <body>
                <h1>CUSTOM HTML SAMPLEt</h1>
                <p>This is a custom HTML page set using setHtml()</p>
            </body>
        </html>
        """
        self.browser.setHtml(custom_html)
    
    def zoom_in(self):
        self.current_zoom += 0.1
        self.browser.setZoomFactor(self.current_zoom)
    
    def zoom_out(self):
        self.current_zoom = max(0.1, self.current_zoom - 0.1)
        self.browser.setZoomFactor(self.current_zoom)
    
    def trigger_page_action(self):
        # Trigger a page refresh action
        self.browser.triggerPageAction(self.browser.page().Reload)

if __name__ == "__main__":
    app = QApplication(sys.argv)
    window = WebBrowserExample()
    window.show()
    sys.exit(app.exec())