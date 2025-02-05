from PySide6.QtWidgets import QTabWidget, QWidget, QVBoxLayout, QLabel
from PySide6.QtCore import Qt
from PySide6.QtGui import QPainter, QColor, QPen, QFont

class CustomTabWidget(QTabWidget):
    def __init__(self, parent=None):
        super().__init__(parent)
        
        # Set up tab appearance
        self.setStyleSheet("""
            QTabWidget::pane {
                border: 1px solid #C2C2C2;
                background: white;
            }
            QTabWidget::tab-bar {
                left: 0px;
            }
        """)
        
        # Initialize some demo tabs
        self.init_demo_tabs()
        
    def init_demo_tabs(self):
        # Create some example tabs
        for i in range(3):
            tab = QWidget()
            layout = QVBoxLayout()
            label = QLabel(f"Content for Tab {i+1}")
            layout.addWidget(label)
            tab.setLayout(layout)
            self.addTab(tab, f"Tab {i+1}")
    
    def tabText(self, index):
        """Override to customize how tab text is retrieved"""
        return super().tabText(index)
    
    def setTabText(self, index, text):
        """Override to customize how tab text is set"""
        super().setTabText(index, text)
    
    def paintEvent(self, event):
        """Custom paint event to draw tabs with text"""
        super().paintEvent(event)
        painter = QPainter(self)
        painter.setRenderHint(QPainter.Antialiasing)
        
        # Set up the font
        font = QFont()
        font.setPointSize(10)
        painter.setFont(font)
        
        # Draw text for each tab
        for i in range(self.count()):
            tab_rect = self.tabRect(i)
            text = self.tabText(i)
            
            # Adjust text position within tab
            text_rect = tab_rect
            
            # Different styling for selected tab
            if i == self.currentIndex():
                painter.setPen(QPen(QColor("#000000")))
                font.setBold(True)
            else:
                painter.setPen(QPen(QColor("#666666")))
                font.setBold(False)
            
            painter.setFont(font)
            painter.drawText(
                text_rect,
                Qt.AlignmentFlag.AlignCenter,
                text
            )

# Example usage
if __name__ == "__main__":
    from PySide6.QtWidgets import QApplication
    import sys
    
    app = QApplication(sys.argv)
    
    # Create and show the custom tab widget
    tab_widget = CustomTabWidget()
    tab_widget.resize(400, 300)
    tab_widget.show()
    
    sys.exit(app.exec())