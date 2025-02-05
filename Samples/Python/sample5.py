from PySide6.QtWidgets import (QApplication, QWizard, QWizardPage, QLabel, QVBoxLayout)
from PySide6.QtGui import QPixmap
from PySide6.QtCore import Qt

class IntroPage(QWizardPage):
    def __init__(self):
        super().__init__()
        self.setTitle("Introduction")
        self.setSubTitle("This is an introduction to the wizard.")
        layout = QVBoxLayout()
        layout.addWidget(QLabel("Welcome to the example wizard!"))
        self.setLayout(layout)

class DetailsPage(QWizardPage):
    def __init__(self):
        super().__init__()
        self.setTitle("Details")
        self.setSubTitle("Provide more information here.")
        layout = QVBoxLayout()
        layout.addWidget(QLabel("Some details about the process."))
        self.setLayout(layout)

class ConclusionPage(QWizardPage):
    def __init__(self):
        super().__init__()
        self.setTitle("Conclusion")
        self.setSubTitle("You have reached the end of the wizard.")
        layout = QVBoxLayout()
        layout.addWidget(QLabel("Thank you for using the wizard!"))
        self.setLayout(layout)

class MyWizard(QWizard):
    def __init__(self):
        super().__init__()
        
        # Adding pages
        self.addPage(IntroPage())
        self.addPage(DetailsPage())
        self.addPage(ConclusionPage())
        
        # Demonstrating setOption() and setOptions()
        self.setOption(QWizard.NoCancelButton, True)  # Removes the Cancel button
        self.setOptions(QWizard.NoBackButtonOnLastPage | QWizard.IndependentPages)  # Multiple options
        
        # Demonstrating setPixmap()
        pixmap = QPixmap("wizard.jpg")
        self.setPixmap(QWizard.LogoPixmap, pixmap)  # Setting custom pixmap
        
        # Demonstrating setTitleFormat() and setSubTitleFormat()
        self.setTitleFormat(Qt.RichText)
        self.setSubTitleFormat(Qt.PlainText)
        
        # Demonstrating setWizardStyle() and wizardStyle()
        self.setWizardStyle(QWizard.ModernStyle)
        print("Current Wizard Style:", self.wizardStyle())
        
        self.setWindowTitle("Python Wizard")

if __name__ == "__main__":
    app = QApplication([])
    wizard = MyWizard()
    wizard.show()
    app.exec()
