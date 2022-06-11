package cmd

import "fmt"

var (
	stability = "dev"
	version   = "0.0.1"
)

func GetCurrentVersion() string {
	return fmt.Sprintf("v%s-%s", version, stability)
}
